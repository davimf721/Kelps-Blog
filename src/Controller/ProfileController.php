<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\{ProfileService, FollowService, AuthService, UploadService};
use App\Security\SessionManager;
use App\View\Renderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProfileController extends BaseController
{
    public function __construct(
        Renderer $view,
        private ProfileService $profile,
        private FollowService  $follows,
        private UploadService  $uploads,
    ) {
        parent::__construct($view);
    }

    // GET /profile/{id}
    public function show(Request $request, Response $response, array $args): Response
    {
        $user = $this->profile->getProfile((int) $args['id']);

        if (! $user) {
            return $this->render($response, 'errors/404')->withStatus(404);
        }

        $isFollowing = $this->isLoggedIn()
            ? $this->follows->isFollowing($this->userId(), (int) $args['id'])
            : false;

        $posts = $this->profile->getUserPosts((int) $args['id']);

        return $this->render($response, 'profile/show', [
            'user'        => $user,
            'posts'       => $posts,
            'isFollowing' => $isFollowing,
            'isOwner'     => $this->userId() === (int) $args['id'],
        ]);
    }

    // GET /profile/edit
    public function edit(Request $request, Response $response): Response
    {
        $user = $this->profile->getProfile($this->userId());

        return $this->render($response, 'profile/edit', ['user' => $user]);
    }

    // POST /profile/edit
    public function update(Request $request, Response $response): Response
    {
        $data = $this->body($request);

        try {
            $this->profile->update($this->userId(), $data);

            // Processar avatar, se enviado
            $files = $request->getUploadedFiles();

            if (! empty($files['avatar']) && $files['avatar']->getError() === UPLOAD_ERR_OK) {
                $path = $this->uploads->handleImage(
                    $this->normalizeUploadedFile($files['avatar']),
                    'avatars'
                );
                $this->profile->updateAvatar($this->userId(), $path);
            }

            // Processar banner, se enviado
            if (! empty($files['banner']) && $files['banner']->getError() === UPLOAD_ERR_OK) {
                $path = $this->uploads->handleImage(
                    $this->normalizeUploadedFile($files['banner']),
                    'banners'
                );
                $this->profile->updateBanner($this->userId(), $path);
            }

            $this->flash('success', 'Perfil atualizado!');
            return $this->redirect($response, '/profile/' . $this->userId());
        } catch (\RuntimeException $e) {
            $user = $this->profile->getProfile($this->userId());
            return $this->render($response, 'profile/edit', [
                'user'  => $user,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // POST /profile/change-password
    public function changePassword(Request $request, Response $response): Response
    {
        $data = $this->body($request);

        try {
            $this->profile->changePassword(
                $this->userId(),
                $data['current_password'] ?? '',
                $data['new_password'] ?? ''
            );

            $this->flash('success', 'Senha alterada com sucesso!');
        } catch (\RuntimeException $e) {
            $this->flash('error', $e->getMessage());
        }

        return $this->redirect($response, '/profile/edit');
    }

    // GET /profile/delete
    public function showDelete(Request $request, Response $response): Response
    {
        return $this->render($response, 'profile/delete');
    }

    // POST /profile/delete
    public function delete(Request $request, Response $response): Response
    {
        $data = $this->body($request);

        try {
            $this->profile->deleteAccount($this->userId(), $data['password'] ?? '');
            SessionManager::destroy();
            $this->flash('info', 'Conta excluída com sucesso.');
            return $this->redirect($response, '/');
        } catch (\RuntimeException $e) {
            return $this->render($response, 'profile/delete', ['error' => $e->getMessage()]);
        }
    }

    // POST /api/users/{id}/follow
    public function follow(Request $request, Response $response, array $args): Response
    {
        if (! $this->isLoggedIn()) {
            return $this->json($response, ['error' => 'Login necessário.'], 401);
        }

        try {
            $result = $this->follows->toggle($this->userId(), (int) $args['id']);
            return $this->json($response, $result);
        } catch (\RuntimeException $e) {
            return $this->json($response, ['error' => $e->getMessage()], 422);
        }
    }

    // GET /api/users/{id}/followers
    public function getFollowers(Request $request, Response $response, array $args): Response
    {
        $followers = $this->follows->getFollowers((int) $args['id']);
        return $this->json($response, $followers);
    }

    // GET /api/users/{id}/following
    public function getFollowing(Request $request, Response $response, array $args): Response
    {
        $following = $this->follows->getFollowing((int) $args['id']);
        return $this->json($response, $following);
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    /** Converte PSR-7 UploadedFileInterface para array nativo do PHP. */
    private function normalizeUploadedFile(mixed $file): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'upload');
        $file->moveTo($tmp);

        return [
            'name'     => $file->getClientFilename(),
            'type'     => $file->getClientMediaType(),
            'tmp_name' => $tmp,
            'error'    => UPLOAD_ERR_OK,
            'size'     => $file->getSize(),
        ];
    }
}

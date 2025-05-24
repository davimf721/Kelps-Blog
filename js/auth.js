

document.addEventListener("DOMContentLoaded", function() {
    const registerForm = document.getElementById("register-form");
    const loginForm = document.getElementById("login-form");
    const registerMessageDiv = document.getElementById("register-message");
    const loginMessageDiv = document.getElementById("login-message");

    if (registerForm) {
        registerForm.addEventListener("submit", function(e) {
            e.preventDefault();
            const usernameInput = registerForm.querySelector("#username");
            const emailInput = registerForm.querySelector("#email");
            const passwordInput = registerForm.querySelector("#password");

            const username = usernameInput.value.trim();
            const email = emailInput.value.trim();
            const password = passwordInput.value;

            // Clear previous messages
            if(registerMessageDiv) registerMessageDiv.textContent = '';

            if (validateRegistration(username, email, password)) {
                // Simulate registration process
                // For a real application, you would send this data to a server.
                // We'll simulate a successful registration here.
                if(registerMessageDiv) registerMessageDiv.textContent = "Usuário cadastrado com sucesso!";
                registerMessageDiv.className = 'message success';
                registerForm.reset();
            } else {
                // Validation errors are handled by alert in validateRegistration for now
                // You might want to display these in registerMessageDiv as well.
                 if(registerMessageDiv && !registerMessageDiv.textContent) { // Only if no specific validation message was set
                    registerMessageDiv.textContent = "Falha no cadastro. Verifique os campos.";
                    registerMessageDiv.className = 'message error';
                }
            }
        });
    }

    if (loginForm) {
        loginForm.addEventListener("submit", function(e) {
            e.preventDefault();
            const usernameInput = loginForm.querySelector("#username");
            // const emailInput = loginForm.querySelector("#email"); // Email is in the form but not used in current validateLogin
            const passwordInput = loginForm.querySelector("#password");

            const username = usernameInput.value.trim();
            const password = passwordInput.value;

            // Clear previous messages
            if(loginMessageDiv) loginMessageDiv.textContent = '';

            // Simulate login process
            // For a real application, you would check credentials against stored user data.
            // We'll simulate a failed login for "Nenhum usuário encontrado"
            // and a successful one for a dummy user.

            if (validateLogin(username, password)) {
                // Simulate checking credentials
                if (username === "testuser" && password === "password123") { // Example credentials
                    if(loginMessageDiv) loginMessageDiv.textContent = "Login successful!";
                    loginMessageDiv.className = 'message success';
                    loginForm.reset();
                    // Redirect to a dashboard or home page
                    // window.location.href = "index.html";
                } else {
                    if(loginMessageDiv) loginMessageDiv.textContent = "Nenhum usuário encontrado ou senha incorreta.";
                    loginMessageDiv.className = 'message error';
                }
            } else {
                // Validation errors are handled by alert in validateLogin for now
                if(loginMessageDiv && !loginMessageDiv.textContent) {
                    loginMessageDiv.textContent = "Falha no login. Verifique os campos.";
                    loginMessageDiv.className = 'message error';
                }
            }
        });
    }

    function validateRegistration(username, email, password) {
        const messageDiv = registerMessageDiv || { textContent: '', className: '' }; // Fallback if not on register page
        if (!username || !email || !password) {
            messageDiv.textContent = "Todos os campos são obrigatórios.";
            messageDiv.className = 'message error';
            return false;
        }
        if (!validateEmail(email)) {
            messageDiv.textContent = "Por favor, insira um endereço de e-mail válido.";
            messageDiv.className = 'message error';
            return false;
        }
        return true;
    }

    function validateLogin(username, password) {
        const messageDiv = loginMessageDiv || { textContent: '', className: '' }; // Fallback if not on login page
        if (!username || !password) {
            // Note: login.html also has an email field, but it's not used in this validation
            messageDiv.textContent = "Nome de usuário e senha são obrigatórios.";
            messageDiv.className = 'message error';
            return false;
        }
        return true;
    }

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    }
});
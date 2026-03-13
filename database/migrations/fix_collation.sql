-- Fix PostgreSQL collation version mismatch
-- Run this in Railway PostgreSQL console or during deployment
-- https://www.postgresql.org/docs/current/sql-alterdatabase.html

-- Rebuild all objects that use the default collation
ALTER DATABASE railway REFRESH COLLATION VERSION;

-- Verify the fix
SELECT datname, datcollate, datctype FROM pg_database WHERE datname = 'railway';

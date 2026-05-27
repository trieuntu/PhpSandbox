-- Create application and sandbox users with appropriate privilege scopes

-- App user: full access to phpsandbox, read-only on sandbox_shared
CREATE USER IF NOT EXISTS 'phpsandbox_app'@'%' IDENTIFIED BY 'app_password';
GRANT ALL PRIVILEGES ON `phpsandbox`.*     TO 'phpsandbox_app'@'%';
GRANT SELECT         ON `sandbox_shared`.* TO 'phpsandbox_app'@'%';

-- Sandbox admin: manages per-student sandbox_ databases and can create users
CREATE USER IF NOT EXISTS 'sandbox_admin'@'%' IDENTIFIED BY 'sandbox_admin_pass';
GRANT ALL PRIVILEGES ON `sandbox_%`.* TO 'sandbox_admin'@'%';
GRANT CREATE USER    ON *.*           TO 'sandbox_admin'@'%';
GRANT GRANT OPTION   ON `sandbox_%`.* TO 'sandbox_admin'@'%';

FLUSH PRIVILEGES;

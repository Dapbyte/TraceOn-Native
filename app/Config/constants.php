<?php

declare(strict_types=1);

define('BCRYPT_COST',         (int)($_ENV['BCRYPT_COST']         ?? 12));
define('SESSION_TIMEOUT',     (int)($_ENV['SESSION_TIMEOUT']     ?? 7200));
define('SESSION_NAME',              $_ENV['SESSION_NAME']        ?? 'traceon_session');
define('UPLOAD_MAX_SIZE',     (int)($_ENV['UPLOAD_MAX_SIZE']     ?? 2097152));
define('UPLOAD_AVATAR_DIR',         $_ENV['UPLOAD_AVATAR_DIR']   ?? '/public/uploads/avatars');
define('APP_ENV',                   $_ENV['APP_ENV']             ?? 'production');
define('APP_DEBUG',                ($_ENV['APP_DEBUG']           ?? 'false') === 'true');

define('UPLOAD_ALLOWED_TYPES', array_map(
    'trim',
    explode(',', $_ENV['UPLOAD_ALLOWED_TYPES'] ?? 'image/jpeg,image/png,image/webp')
));

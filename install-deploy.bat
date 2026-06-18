php composer.phar config cache-dir --unset
php composer.phar config vendor-dir --unset
php composer.phar config bin-dir --unset
@rmdir /s /q %USERPROFILE%\symfony
php composer.phar install
npm install && npm run deploy && php bin/console cache:clear && @rmdir /s /q node_modules
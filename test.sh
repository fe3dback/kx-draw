mkdir -p build/logs
php vendor/bin/phpunit -c phpunit.xml
php vendor/bin/phpcov merge --clover build/logs/clover.xml build/cov
php vendor/bin/coveralls
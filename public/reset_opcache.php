<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache reseteada correctamente.";
} else {
    echo "OPcache no está habilitado.";
}

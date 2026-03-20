<?php

$isWindows = PHP_OS_FAMILY === 'Windows';

// Detecta el sistema operativo para armar el comando de desarrollo.
// En Windows no se incluye "pail" porque requiere la extensión pcntl que no está disponible.

if ($isWindows) {
  $command = 'npx concurrently -c "#93c5fd,#c4b5fd,#fdba74" "php artisan serve" "php artisan queue:listen --tries=1 --timeout=0" "npm run dev" --names=server,queue,vite --kill-others';
} else {
  $command = 'npx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" "php artisan serve" "php artisan queue:listen --tries=1 --timeout=0" "php artisan pail --timeout=0" "npm run dev" --names=server,queue,logs,vite --kill-others';
}

// Se usa proc_open en lugar de passthru para que los procesos hijos escriban
// directamente en la terminal (heredan STDIN, STDOUT y STDERR).
// Con passthru, la salida pasa por un pipe de PHP; al presionar Ctrl+C, PHP muere
// primero y el pipe se rompe, causando errores EPIPE en concurrently.
$process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes);

if (!is_resource($process)) {
  exit(1);
}

// En Linux, después de lanzar el proceso hijo, se ignoran las señales SIGINT y SIGTERM
// en PHP para que no muera antes que concurrently. El Ctrl+C llega igual al proceso hijo
// (porque tiene su propio grupo de procesos), y PHP espera con proc_close a que termine
// de forma ordenada, evitando así el error EPIPE y los mensajes encadenados de Composer.
if (!$isWindows && extension_loaded('pcntl')) {
  pcntl_async_signals(true);
  pcntl_signal(SIGINT, SIG_IGN);
  pcntl_signal(SIGTERM, SIG_IGN);
}

$exitCode = proc_close($process);

// Ctrl+C genera códigos de salida especiales según el sistema operativo:
//   - Windows: -1073741510 (STATUS_CONTROL_C_EXIT)
//   - Linux:   130 (SIGINT) o 143 (SIGTERM)
// Se normalizan a 0 para que Composer no los interprete como un error real
// y no muestre mensajes adicionales de "Script returned with error code".
if (in_array($exitCode, [-1073741510, 130, 143], true)) {
  $exitCode = 0;
}

exit($exitCode);

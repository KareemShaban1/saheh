<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $modulesPath = base_path('Modules');

        if (!is_dir($modulesPath)) {
            \Log::warning('Modules directory not found', ['path' => $modulesPath]);
            return;
        }

        $this->registerModuleProviders($modulesPath);
    }

    /**
     * Recursively register all module service providers.
     */
    private function registerModuleProviders(string $basePath): void
    {
        $directories = $this->getAllDirectories($basePath);

        // Debug: list all found directories
        \Log::info('Discovered module directories', ['dirs' => $directories]);

        foreach ($this->getAllDirectories($basePath) as $dir) {
            $providerPath = $dir . '/Providers';
            foreach (glob($providerPath . '/*ServiceProvider.php') as $providerFile) {
                $class = $this->getClassFromFile($providerFile);
                if (class_exists($class)) {
                    app()->register($class);
                }
            }
        }

    }

    /**
     * Get all subdirectories recursively (works on Windows too).
     */
    private function getAllDirectories($path)
{
    $directories = [];

    if (!is_dir($path)) {
        return $directories;
    }

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isDir() && file_exists($file->getRealPath() . '/Providers')) {
            $directories[] = $file->getRealPath();
        }
    }

    return $directories;
}


    /**
     * Convert a file path to its full class namespace.
     */
    private function getClassFromFile(string $filePath): string
    {
        $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $filePath);
        $class = str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relativePath);

        return $class; // e.g. Modules\Clinic\Announcement\Providers\AnnouncementServiceProvider
    }
}

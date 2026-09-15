<?php

namespace App\Tasks;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

final class SynchronizeCarSourceRepositoryTask
{
    public function run(string $sourcePath, string $repositoryUrl): void
    {
        if (is_dir($sourcePath . DIRECTORY_SEPARATOR . '.git')) {
            $result = Process::path($sourcePath)->timeout(120)->run(['git', 'pull', '--ff-only']);

            if ($result->successful()) {
                return;
            }

            throw new RuntimeException('Unable to update source repository: ' . trim($result->errorOutput()));
        }

        if (is_dir($sourcePath)) {
            throw new RuntimeException("Source path exists but is not a Git repository: {$sourcePath}. Use --no-sync to import it as-is.");
        }

        File::ensureDirectoryExists(dirname($sourcePath));
        $result = Process::timeout(120)->run(['git', 'clone', '--depth=1', $repositoryUrl, $sourcePath]);

        if (!$result->successful()) {
            throw new RuntimeException('Unable to clone source repository: ' . trim($result->errorOutput()));
        }
    }
}

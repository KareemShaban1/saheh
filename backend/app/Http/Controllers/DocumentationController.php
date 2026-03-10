<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;

class DocumentationController extends Controller
{
    protected function getVisibleFiles(): array
    {
        $config = config('documentation.visible_files', []);
        $basePath = config('documentation.path', base_path('docs'));

        if ($config === 'all' || (is_array($config) && in_array('all', $config))) {
            $files = File::files($basePath);
            $result = [];
            foreach ($files as $file) {
                if (strtolower($file->getExtension()) === 'md') {
                    $result[] = [
                        'filename' => $file->getFilename(),
                        'title' => $this->filenameToTitle($file->getFilename()),
                    ];
                }
            }
            usort($result, fn ($a, $b) => strcasecmp($a['title'], $b['title']));
            return $result;
        }

        $result = [];
        foreach ($config as $key => $value) {
            $filename = is_int($key) ? $value : $key;
            $title = is_int($key) ? $this->filenameToTitle($value) : $value;
            $path = $basePath . DIRECTORY_SEPARATOR . $filename;
            if (File::exists($path) && strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'md') {
                $result[] = ['filename' => $filename, 'title' => $title];
            }
        }

        return $result;
    }

    protected function filenameToTitle(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        return str_replace(['_', '-'], ' ', $name);
    }

    public function login(Request $request)
    {
        $password = config('documentation.password');

        if (empty($password)) {
            return redirect()->route('docs.index');
        }

        if ($request->isMethod('post')) {
            $request->validate([
                'password' => 'required|string',
            ]);

            if ($request->password === $password) {
                $request->session()->put('docs_authenticated', true);
                return redirect()->intended(route('docs.index'));
            }

            return back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }

        return view('documentation.login');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('docs_authenticated');
        return redirect()->route('docs.login');
    }

    public function index()
    {
        $files = $this->getVisibleFiles();
        $defaultDoc = config('documentation.default_document');

        if (empty($files)) {
            return view('documentation.index', [
                'files' => [],
                'content' => '<p>No documentation files configured. Add files to <code>config/documentation.php</code>.</p>',
                'currentFile' => null,
                'title' => 'Documentation',
            ]);
        }

        $currentFile = $files[0]['filename'] ?? null;
        foreach ($files as $file) {
            if ($file['filename'] === $defaultDoc) {
                $currentFile = $file['filename'];
                break;
            }
        }

        return $this->show($currentFile);
    }

    public function show(?string $file = null)
    {
        $files = $this->getVisibleFiles();
        $basePath = config('documentation.path', base_path('docs'));

        if (empty($file)) {
            return redirect()->route('docs.index');
        }

        // Sanitize: only allow filename, no path traversal
        $file = basename($file);
        $path = $basePath . DIRECTORY_SEPARATOR . $file;

        $allowedFilenames = array_column($files, 'filename');
        if (! in_array($file, $allowedFilenames)) {
            abort(404, 'Document not found.');
        }

        if (! File::exists($path)) {
            abort(404, 'Document not found.');
        }

        $markdown = File::get($path);
        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $content = (string) $converter->convert($markdown);

        $currentTitle = $this->filenameToTitle($file);
        foreach ($files as $f) {
            if ($f['filename'] === $file) {
                $currentTitle = $f['title'];
                break;
            }
        }

        return view('documentation.index', [
            'files' => $files,
            'content' => $content,
            'currentFile' => $file,
            'title' => $currentTitle,
        ]);
    }
}

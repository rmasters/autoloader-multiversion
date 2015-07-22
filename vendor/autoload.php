<?php


class Dependencies
{
    protected $rootPackages = [];
    protected $packages = [];
    protected $classmap = [];
    protected $replacements = [];

    public function parseTree(array $tree, $parent = null) {
        foreach ($tree as $packageName => $dependencies) {
            $package = static::pkg($packageName);

            if (is_null($parent)) {
                $this->rootPackages[] = $packageName;
            }

            if (!array_key_exists($packageName, $this->packages)) {
                $this->packages[$packageName] = [];
            }
            foreach ($dependencies as $depName => $depDeps) {
                $this->packages[$packageName][] = $depName;
            }
            
            $this->parseTree($dependencies, $packageName);
        }
    }

    public function getPackages() {
        return array_map(['self', 'pkg'], array_keys($this->packages));
    }

    public function getClassmaps() {
        $maps = [];
        foreach ($this->getPackages() as $pkg) {
            $maps[$pkg['ref']] = $this->getClassMap($pkg['name'], $pkg['version']);
        }
        return $maps;
    }

    public function createVersionedClasses() {
        $classmaps = $this->getClassmaps();
        foreach ($classmaps as $pkg => $classmap) {
            $pkg = self::pkg($pkg);
            $dir = $this->getClassCachePath($pkg['name'], $pkg['version']);
            // @todo Assumption. Should define this per-package
            $rootNs = implode('\\', array_map('ucfirst', explode('/', $pkg['name'])));

            foreach ($classmap as $fqcn => $path) {
                $newFqcn = $this->versionFQCN($rootNs, $pkg['version'], $fqcn);
                $newPath = $dir . '/' . substr($path, strlen($this->getPackagePath($pkg['name'], $pkg['version']))+1);

                $this->duplicateClass($path, $newPath, $fqcn, $newFqcn);
                $this->classmap[$newFqcn] = $newPath;

                if (!array_key_exists($pkg['ref'], $this->replacements)) {
                    $this->replacements[$pkg['ref']] = [];
                }
                $this->replacements[$pkg['ref']][$fqcn] = $newFqcn;
            }
        }
    }

    protected function duplicateClass($srcPath, $destPath, $oldFQCN, $newFQCN) {
        $sub = [
            // "namespace ns(oldFQCN)" => "namespace ns(newFQCN)"
            '/namespace\s+' . preg_quote(self::ns($oldFQCN), '/') . '/' => 'namespace ' . preg_quote(self::ns($newFQCN), '/'),
            // "class cls(oldFQCN)" => "class cls(newFQCN)"
            '/class\s+' . preg_quote(self::cls($oldFQCN), '/') . '/' => 'class ' . preg_quote(self::cls($newFQCN), '/'),
        ];

        file_put_contents(
            $destPath,
            preg_replace(
                array_keys($sub),
                array_values($sub),
                file_get_contents($srcPath)
            )
        );
    }

    public function updateDependencyReferences() {
        /*
            Iterate through each package with dependencies,
            replace each cached file
            replace references to old fqcns with new fqcns based on the dependency
        */

        foreach ($this->packages as $package => $dependencies) {
            $filesToUpdate = array_values(array_map(function($newFqcn) {
                return $this->classmap[$newFqcn];
            }, $this->replacements[$package]));

            $sub = [];
            foreach ($dependencies as $dependency) {
                foreach ($this->replacements[$dependency] as $oldFqcn => $newFqcn) {
                    $sub['/' . preg_quote($oldFqcn, '/') . '/'] = $newFqcn;
                }
            }

            foreach ($filesToUpdate as $path) {
                file_put_contents(
                    $path,
                    preg_replace(
                        array_keys($sub),
                        array_values($sub),
                        file_get_contents($path)
                    )
                );
            }
        }
    }

    public function aliasRootPackages() {
        foreach ($this->rootPackages as $package) {
            foreach ($this->replacements[$package] as $origFqcn => $newFqcn) {
                require_once $this->classmap[$newFqcn];
                class_alias($newFqcn, $origFqcn, false);  
            }
        }
    }

    public function autoload($className) {
        require_once $this->classmap[$className];
    }

    protected function getPackagePath($package, $version) {
        return __DIR__ . '/' . $package . '@' . $version;
    }

    protected function getClassmap($package, $version) {
        return require $this->getPackagePath($package, $version) . '/classmap.php';
    }

    protected function versionNamespace($rootNs, $version, $ns) {
        $version = $this->normalizeVersion($version);
        return sprintf('%s\\%s\\%s', $rootNs, $version, substr($ns, strlen($rootNs)));
    }

    protected function versionFQCN($rootNs, $version, $fqcn) {
        $version = $this->normalizeVersion($version);
        return sprintf('%s\\%s\\%s', $rootNs, $version, substr($fqcn, strlen($rootNs)+1));
    }

    protected function normalizeVersion($version) {
        return 'v' . ltrim(preg_replace('/[^0-9]+/', '_', $version), 'v');
    }

    protected function getClassCachePath($package, $version) {
        $dir = __DIR__ . '/cache/' . $package . '@' . $version;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir;
    }

    public static function pkg($packageStr) {
        preg_match('/(?P<ref>(?P<name>(?P<vendor>[A-Za-z]+)\/(?P<package>[A-Za-z]+))@(?P<version>[0-9.]+))/', $packageStr, $match);
        return array_intersect_key($match, array_flip(['name', 'vendor', 'package', 'version', 'ref']));
    }

    public static function ns($fqcn) {
        return substr($fqcn, 0, strrpos($fqcn, '\\'));
    }

    public static function cls($fqcn) {
        return substr($fqcn, strrpos($fqcn, '\\')+1);
    }
}

$dependencies = new Dependencies;

// Init:
$project = json_decode(file_get_contents(__DIR__ . '/../dependencies.json'), true);
$dependencies->parseTree($project['require']);

// Pass 1:
$dependencies->createVersionedClasses();

// Pass 2:
$dependencies->updateDependencyReferences();

// Autoload
$dependencies->aliasRootPackages();
spl_autoload_register([$dependencies, 'autoload']);

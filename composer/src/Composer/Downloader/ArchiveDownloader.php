<?php











namespace Composer\Downloader;

use Composer\Package\PackageInterface;
use Symfony\Component\Finder\Finder;
use Composer\IO\IOInterface;








abstract class ArchiveDownloader extends FileDownloader
{



public function download(PackageInterface $package, $path, $output = true)
{
$temporaryDir = $this->config->get('vendor-dir').'/composer/'.substr(md5(uniqid('', true)), 0, 8);
$retries = 3;
while ($retries--) {
$fileName = parent::download($package, $path, $output);

$this->io->writeError(' Extracting archive', false, IOInterface::VERBOSE);

try {
$this->filesystem->ensureDirectoryExists($temporaryDir);
try {
$this->extract($fileName, $temporaryDir);
} catch (\Exception $e) {

 parent::clearLastCacheWrite($package);
throw $e;
}

$this->filesystem->unlink($fileName);

$contentDir = $this->getFolderContent($temporaryDir);


 if (1 === count($contentDir) && is_dir(reset($contentDir))) {
$contentDir = $this->getFolderContent((string) reset($contentDir));
}


 foreach ($contentDir as $file) {
$file = (string) $file;
$this->filesystem->rename($file, $path . '/' . basename($file));
}

$this->filesystem->removeDirectory($temporaryDir);
if ($this->filesystem->isDirEmpty($this->config->get('vendor-dir').'/composer/')) {
$this->filesystem->removeDirectory($this->config->get('vendor-dir').'/composer/');
}
if ($this->filesystem->isDirEmpty($this->config->get('vendor-dir'))) {
$this->filesystem->removeDirectory($this->config->get('vendor-dir'));
}
} catch (\Exception $e) {

 $this->filesystem->removeDirectory($path);
$this->filesystem->removeDirectory($temporaryDir);


 if ($retries && $e instanceof \UnexpectedValueException && class_exists('ZipArchive') && $e->getCode() === \ZipArchive::ER_NOZIP) {
$this->io->writeError('');
if ($this->io->isDebug()) {
$this->io->writeError('    Invalid zip file ('.$e->getMessage().'), retrying...');
} else {
$this->io->writeError('    Invalid zip file, retrying...');
}
usleep(500000);
continue;
}

throw $e;
}

break;
}
}




protected function getFileName(PackageInterface $package, $path)
{
return rtrim($path.'/'.md5($path.spl_object_hash($package)).'.'.pathinfo(parse_url($package->getDistUrl(), PHP_URL_PATH), PATHINFO_EXTENSION), '.');
}









abstract protected function extract($file, $path);







private function getFolderContent($dir)
{
$finder = Finder::create()
->ignoreVCS(false)
->ignoreDotFiles(false)
->notName('.DS_Store')
->depth(0)
->in($dir);

return iterator_to_array($finder);
}
}

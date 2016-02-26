<?php

namespace Codebender\LibraryBundle\Handler\ApiCommand;

use Symfony\Component\Finder\Finder;

class ListApiCommand extends AbstractApiCommand
{
    public function execute($content)
    {
        $arduinoLibraryFiles = $this->container->getParameter('builtin_libraries') . "/";

        $builtinExamples = $this->getLibariesListFromDir($arduinoLibraryFiles . "examples");
        $includedLibraries = $this->getLibariesListFromDir($arduinoLibraryFiles . "libraries");
        /*
         * External libraries list is fetched from the database, because we need to list
         * active libraries only
         */
        $log = fopen('log.log', 'w');
        fwrite($log, 'start' . "\n");
        fclose($log);
        $externalLibraries = $this->getExternalLibrariesList();
        $log = fopen('log.log', 'a');
        fwrite($log, 'end' . "\n");
        fclose($log);

        ksort($builtinExamples);
        ksort($includedLibraries);
        ksort($externalLibraries);

        return [
            'success' => true,
            'text' => 'Successful Request!',
            'categories' => [
                'Examples' => $builtinExamples,
                'Builtin Libraries' => $includedLibraries,
                'External Libraries' => $externalLibraries
            ]
        ];
    }

    /*
     * Copied from DefaultController.php
     */
    private function getLibariesListFromDir($path)
    {

        $finder = new Finder();
        $finder->files()->name('*.ino')->name('*.pde');
        $finder->in($path);

        $libraries = array();

        foreach ($finder as $file) {
            $names = $this
                ->getExampleAndLibNameFromRelativePath(
                    $file->getRelativePath(),
                    $file->getBasename("." . $file->getExtension())
                );

            if (!isset($libraries[$names['library_name']])) {
                $libraries[$names['library_name']] = array("description" => "", "examples" => array());
            }
            $libraries[$names['library_name']]['examples'][] = array('name' => $names['example_name']);
        }
        return $libraries;
    }

    /*
     * Copied from DefaultController.php
     */
    private function getExternalLibrariesList()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $externalMeta = $entityManager
            ->getRepository('CodebenderLibraryBundle:Library')
            ->findBy(array('active' => true));

        $log = fopen('log.log', 'a');
        fwrite($log, '  flag1' . "\n");
        fclose($log);

        $libraries = array();
        foreach ($externalMeta as $library) {
            $libraryDefaultHeader = $library->getDefaultHeader();
            if (!isset($libraries[$libraryDefaultHeader])) {
                $libraries[$libraryDefaultHeader] = array(
                    "description" => $library->getDescription(),
                    "humanName" => $library->getName(),
                    "examples" => array()
                );

                $log = fopen('log.log', 'a');
                fwrite($log, '      flag2' . "\n");
                fclose($log);

                if ($library->getOwner() !== null && $library->getRepo() !== null) {
                    $libraries[$libraryDefaultHeader] = array(
                        "description" => $library->getDescription(),
                        "humanName" => $library->getName(),
                        "url" => "http://github.com/" . $library->getOwner() . "/" . $library->getRepo(),
                        "examples" => array()
                    );
                }

                $log = fopen('log.log', 'a');
                fwrite($log, '      flag3' . "\n");
                fclose($log);
            }

            $examples = $entityManager
                ->getRepository('CodebenderLibraryBundle:LibraryExample')
                ->findBy(array('library' => $library));

            $log = fopen('log.log', 'a');
            fwrite($log, '      flag4' . "\n");
            fclose($log);

            foreach ($examples as $example) {
                $names = $this
                    ->getExampleAndLibNameFromRelativePath(
                        pathinfo($example->getInRepoPath(), PATHINFO_DIRNAME),
                        $example->getName()
                    );

                $libraries[$libraryDefaultHeader]['examples'][] = array('name' => $names['example_name']);
            }
        }

        return $libraries;
    }

    /*
     * Copied from DefaultController.php
     */
    private function getExampleAndLibNameFromRelativePath($path, $filename)
    {
        $type = "";
        $libraryName = strtok($path, "/");

        $tmp = strtok("/");

        while ($tmp != "" && !($tmp === false)) {
            if ($tmp != 'examples' && $tmp != 'Examples' && $tmp != $filename) {
                if ($type == "") {
                    $type = $tmp;
                } else {
                    $type = $type . ":" . $tmp;
                }
            }
            $tmp = strtok("/");
        }
        $exampleName = ($type == "" ? $filename : $type . ":" . $filename);
        return (array('library_name' => $libraryName, 'example_name' => $exampleName));
    }
}

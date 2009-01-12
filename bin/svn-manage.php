#/usr/bin/env php
/**
 * svn-manage
 *
 * PHP Version 5
 *
 * Oh -- the irony, a script to deal with SVN, hosted with GIT!
 *
 * @category system
 * @package  svn-manage
 * @author   Till Klampaeckel <till@php.net>
 * @license  http://foo New BSD License
 * @link     http://till.klampaeckel.de/blog/
 * @todo     Use another PEAR package to parse 'args'.
 * @todo     Clean-up code, more OO, less procedural, some docs.
 */
<?php
$svn_client = '/usr/bin/env svn';
$svn_user   = '';
$svn_passwd = '';


if (!is_executable($svn_client)) {
    die('Could not find svn client.');
}
$svn_cmd = svn_command();

$path = getPath();

$files = list($path);

if (count($files) == 0) {
    die('Nothing found, you are all set!');
}

bulk($files, $path);


// FUNCTION LIB

function getPath()
{
    if ($_SERVER['argc'] == 0 || $_SERVER['argc'] == 1) {
        die('Please use ./file path');
    }
    $path = $_SERVER['argv'][1];
    if (!file_exists($path)) {
        die('Supplied path does not exist.');
    }
    if (!is_readable($path)) {
        die('Supplied path is not readable.');
    }
    return $path;
}

function list($path)
{
    global $svn_client;
    $files = exec("{$svn_client} status {$path}");
    return $files;
}

function bulk(array $files, $path)
{
    foreach ($files as $file) {
        $st = substr($file, 0, 2);
        $f  = trim(str_replace($st, '', $file));
        
        switch ($st) {
        case '! ':
            svn_delete($f);
            break;
        case '? ':
            svn_add($f);
            break;
        case 'M ':
            break;
        }
    }
    svn_commit($path);
}

function svn_delete($file)
{
    global $svn_cmd;
    exec("{$svn_cmd} delete --force {$file}");
}

function svn_add($file)
{
    global $svn_cmd;
    exec("{$svn_cmd} add {$file}");
}

function svn_commit($path)
{
    global $svn_cmd;
    exec("{$svn_cmd} commit -m '* cleanup, using svn-manage' {$path}");
}

function svn_command()
{
    global $svn_client, $svn_user, $svn_passwd;

    $cmd  = $svn_client;
    $cmd .= ' --non-interactive';
    if (!empty($svn_user)) {
        $cmd .= " --username {$svn_user}";
        if (!empty($svn_passwd)) {
            $cmd .= " --password {$svn_passwd}";
            $cmd .= " --no-auth-cache";
        }
    }

    return $cmd;
}
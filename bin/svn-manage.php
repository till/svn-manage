#!/usr/bin/env php
<?php
/**
 * svn-manage
 *
 * PHP Version 5
 *
 * Oh -- the irony, a script to deal with SVN, hosted on GITHUB!
 *
 * @category System
 * @package  Lagged_Subversion_Manage
 * @author   Till Klampaeckel <till@php.net>
 * @version  GIT: $Id$
 * @license  http://www.opensource.org/licenses/bsd-license.php The New BSD License
 * @link     http://github.com/till/svn-manage/tree/master
 * @link     http://till.klampaeckel.de/blog/
 * @todo     Use another PEAR package to parse 'args'.
 * @todo     Clean-up code, more OO, less procedural, some docs.
 */

$base = dirname('@php_bin@');

$svn_client = $base . '/svn';

/**
 * @global
 */
$svn_user   = '';
$svn_passwd = '';

/**
 * Console_CommandLine
 * @ignore
 */
require_once 'Console/CommandLine.php';

$parser = new Console_CommandLine();

$parser->description = 'A tool to mass-manage changes in an SVN repository';
$parser->version     = '@package_version@';

$parser->addOption('username', array(
    'short_name'  => '-u',
    'long_name'   => '--username',
    'description' => 'A username for Subversion.',
    'action'      => 'StoreString'
));
$parser->addOption('password', array(
    'short_name'  => '-p',
    'long_name'   => '--password',
    'description' => "A password for Subversion.",
    'action'      => 'Password'
));
$parser->addOption('target', array(
    'short_name'  => '-t',
    'long_name'   => '--target',
    'description' => "The path to your repository.",
    'action'      => 'StoreString'
));

try {
    $result = $parser->parse();
    if (isset($result->options['username'])) {
        $svn_user .= $result->options['username'];
        if (isset($result->options['passwd'])) {
            $svn_passwd .= $result->options['password'];
        }
    }
    if (!isset($result->options['target'])) {
        echo "Please supply a target path.\n";
        exit(0);
    }
    $target = $result->options['target'];
} catch (Console_CommandLine_Exception $e) {
    echo $e->getMessage() . "\n";
    exit(0);
}

/**
 * Console_ProgressBar
 * @ignore
 */
require_once 'Console/ProgressBar.php';

if (!is_executable($svn_client)) {
    echo "Could not find svn client: {$svn_client}\n";
    exit(0);
}
$svn_cmd = svn_command();

$path = getPath();

$files = listTodo($path);

if (count($files) == 0) {
    echo "Nothing found, you are all set!\n";;
    exit(1);
}
bulk($files, $path);


// FUNCTION LIB

/**
 * Test the target path.
 *
 * @return string
 */
function getPath()
{
    global $target;

    if (!file_exists($target)) {
        echo "Supplied path does not exist.\n";
        exit(0);
    }
    if (!is_readable($target)) {
        echo "Supplied path is not readable.\n";
        exit(0);
    }
    return $target;
}

/**
 * Get whatever needs to be done.
 *
 * @param string $path The path of the repository.
 *
 * @return array
 */
function listTodo($path)
{
    global $svn_client;
    ob_start();
    system("{$svn_client} status {$path}");
    $files = ob_get_contents();
    ob_end_clean();
    $files = explode("\n", $files);
    return $files;
}

/**
 * @param array  $files The files to work.
 * @param string $path  The path of the repository.
 *
 * @return void
 * @uses   Console_ProgressBar::__construct()
 * @uses   svn_delete()
 * @uses   svn_add()
 * @uses   svn_commit()
 */
function bulk(array $files, $path)
{
    $bar = new Console_ProgressBar('[%bar%] %percent%', '=>', ' ', 80, count($files));
    $x   = 0;
    foreach ($files as $file) {
        if (empty($file)) {
            continue;
        }
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
        $x++;
        $bar->update($x);
    }
    $bar->erase();

    echo "\n\tCommitting files...";

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
    //$cmd .= ' --non-interactive';
    if (!empty($svn_user)) {
        $cmd .= " --username {$svn_user}";
        if (!empty($svn_passwd)) {
            $cmd .= " --password {$svn_passwd}";
            $cmd .= " --no-auth-cache";
        }
    }

    return $cmd;
}
echo "\n";
exit(1);

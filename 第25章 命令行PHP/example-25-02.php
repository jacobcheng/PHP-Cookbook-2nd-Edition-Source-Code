// Load the readline library
if (! function_exists('readline')) {
    dl('readline.'. (((strtoupper(substr(PHP_OS,0,3))) == 'WIN')?'dll':'so'))
        or die("Readline library required\n");
}

// Load the Console_Getopt class
require 'Console/Getopt.php';

$o = new Console_Getopt;
$opts = $o->getopt($o->readPHPArgv(),'hm',array('help','multiline'));

// Quit with a usage message if the arguments are bad
if (PEAR::isError($opts)) {
    print $opts->getMessage();
    print "\n";
    usage();
}

// Default is to evaluate each command as it's entered
$multiline = false;

foreach ($opts[0] as $opt) {
    // Remove any leading -s
    $opt[0] = preg_replace('/^-+/','',$opt[0]);

    // Check the first character of the argument
    switch($opt[0][0]) {
    case 'h':
        // display help
        usage();
        break;
    case 'm':
        $multiline = true;
        break;
    }
}

// Set up error display
ini_set('display_errors',false);
ini_set('log_errors',true);

// Build readline completion table
$functions = get_defined_functions();
foreach ($functions['internal'] as $k => $v) {
    $functions['internal'][$k] = "$v(";
}
function function_list($line) {
    return $GLOBALS['functions']['internal'];
}
readline_completion_function('function_list');

$cmd = '';
$cmd_count = 1;

while (true) {
    // Get a line of input from the user
    $s = readline("[$cmd_count]> ");
    // Add it to the command history
    readline_add_history($s);
    // If we're in multiline mode:
    if ($multiline) {
        // if just a "." has been entered
        if ('.' == rtrim($s)) {
            // eval() the code
            eval($cmd);
            // Clear out the accumulated code
            $cmd = '';
            // Increment the command count
            $cmd_count++;
            // Start the next prompt on a new line
            print "\n";
        } else {
            /* Otherwise, add the new line to the accumulated code
               tacking on a newline prevents //-style comments from
               commenting out the rest of the lines entered
            */
            $cmd .= $s."\n";;
        }
    } else {
        // If we're not in multiline mode, eval() the line
        eval($s);
        // Increment the command count
        $cmd_count++;
        // Start the next prompt in a new line
        print "\n";
    }
}

// Display helpful usage information
function usage() {
    $my_name = $argv[0];

    print<<<_USAGE_
Usage: $my_name [-h|--help] [-m|--multiline]

  -h, --help: display this help
  -m, --multiline: execute accumulated code when "." is entered
                   by itself on a line. The default is to execute
                   each line after it is entered.    

_USAGE_;
    exit(-1);
}
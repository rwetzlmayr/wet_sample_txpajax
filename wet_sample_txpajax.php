<?php
$plugin['version'] = '0.1';
$plugin['author'] = 'Robert Wetzlmayr';
$plugin['author_uri'] = 'http://wetzlmayr.com/';
$plugin['description'] = 'Sample AJAX processing for Textpattern CMS';

// Plugin 'type' defines where the plugin is loaded
// 0 = public       : only on the public side of the website (default)
// 1 = public+admin : on both the public and non-AJAX admin side
// 2 = library      : only when include_plugin() or require_plugin() is called
// 3 = admin        : only on the non-AJAX admin side
// 4 = admin+ajax   : only on admin side
// 5 = public+admin+ajax   : on both the public and admin side
$plugin['type'] = 4;

if (!defined('txpinterface'))
	@include_once('zem_tpl.php');

if (0) {
?>
# --- BEGIN PLUGIN HELP ---

h2.  Sample AJAX forms and links for Textpattern 4.5+

Textpattern supports declarative AJAX forms and links. This plugin adds a new tab titled "wet_sample_ajax" under the "Extensions" tab.

It renders a form with one input which is processed by an aynchronous server-side backend. The server respondes with a javascript fragment to render the input value into the forms H2 element.

It also renders an asynchronously processed link. The server responds toogles the link text from 'Hello' to 'Good-bye' and vice-versa.

# --- END PLUGIN HELP ---
<?php
}

# --- BEGIN PLUGIN CODE ---
class wet_sample_txpajax
{
	static $my_name;
	static $greeting;

	/**
	 * The constructor merges this plugin with the core system.
	 */
	function __construct()
	{
		// In a full-fledged plugin your data would probably come from the database, or other more useful sources
		// but for educational purposes we content ourselves with this constants.
		self::$my_name = 'Donald Swain'; // Some things just never change...
		self::$greeting = array('Hello', 'Good-bye');

		// Everybody may use this extension
		add_privs(__CLASS__, '1,2,3,4,5,6');

		// Our user interface lives as a separate tab under 'Extensions'
		register_tab('extensions', __CLASS__, gTxt(__CLASS__));

		// This plugin has a single entry point 'dispatch' for its sole event
		register_callback(array(__CLASS__, 'dispatch'), __CLASS__);
	}

	/**
	 * Dispatch the invoked handler by looking into the global $step
	 */
	static function dispatch()
	{
		global $step;
		require_privs(__CLASS__);

		switch ($step) {
			case 'my_name_is':
				self::my_name_is();
				break;
			case 'say_hi':
				self::say_hi();
				break;
			default:
				self::ui();
				break;
		}
	}

	/**
	 * Paint our user interface:
	 * - A form which sends a new user name to the server. This is done by AJAX and thus requires no page reload.
	 * - An asynchronous link which shows the server's response when clicked.
	 */
	static function ui()
	{
		pagetop(gTxt(__CLASS__));

		// Parameters for the AJAX link
		// 'event', 'step', 'thing', and 'property' are optional array members. See txplib_html.php for their default values.
		$async_params = array(
			'step' => 'say_hi', // This tells dispatch() which function to use as a response handler
			'thing' => '', 		// Some response handlers may need more context which may be put in 'thing' and 'property'.
			'property' => ''	// We just leave them empty here, and we could omit 'thing' and 'property' as well as the core uses defaults.
		);

		// Build a AJAX link
		$greez = asyncHref(self::$greeting[0], $async_params);

		// Build the output fragment with a well-known id
		$patron = '<span id="my_name_output">'.self::$my_name.'</span>';

		echo '<div class="txp-edit">'.n.
			// The AJAX link toggles between the two greetings
			hed($greez.' '.$patron, 2).n.
			// The form accepts a new name
			form(
				inputLabel('my_name', fInput('text', 'my_name', self::$my_name, '', '', '', INPUT_REGULAR, '', 'my_name'), 'What is your name?').n.
				graf(fInput('submit', '', gTxt('save'), 'publish')).
				eInput(__CLASS__).
				sInput('my_name_is'). // This tells dispatch() which function to use as a response handler
			'</div>',
			'',
			'',
			'post',
			'async',  // IMPORTANT: 'async' is a class with a special meaning: It triggers Textpattern's AJAX bejaviour
			'',
			__CLASS__
		);
	}

	/**
	 * AJAX response handler for the 'my_name_is' step
	 */
	static function my_name_is()
	{
		// Grab the new name from POST data
		$in = ps('my_name');

		// ...further processing might go here: Database updates, input validation, ...
		self::$my_name = $in;

		// Prepare response string
		$in = escape_js($in);

		// Send a javascript response to render this posted data back into the document's headline.
		// Find the target HTML fragment via jQuery through its selector '#my_name_output'
		// and replace its text.
		send_script_response(<<<EOS
		$('#my_name_output').text('{$in}');
EOS
);
	}

	/**
	 * AJAX response handler for the say_hi' step
	 */
	static function say_hi()
	{
		// Grab the current greeting text from $_POST['value']
		$in = ps('value');

		// Toggle greeting text
		if ($in == self::$greeting[0]) {
			$out = self::$greeting[1];
		} else {
			$out = self::$greeting[0];;
		}
		// Reply opposite greeting back to client
		echo $out;
	}
}

// Start this plugin on the admin-side;
if (txpinterface == 'admin') new wet_sample_txpajax;

# --- END PLUGIN CODE ---
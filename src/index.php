<?php
	require_once('helpers/Controls.Helper.php');
	require_once('helpers/Storage.Helper.php');
	require_once('helpers/Session.Helper.php');

    Session::DetectSession(isset($_GET['createAccount']), isset($_GET['createAccount']) ? null : 'beta');

	switch (isset($_GET['view']) ? $_GET['view'] : ($_GET['view'] = null))
	{
	    case 'notsupported':
	    	require_once('views/NotSupported.View.php');
			$view = new NotSupported();
			break;

	    default:
			require_once('views/TaskLists.View.php');
			$view = new TaskLists();
			break;
	}
?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="Content-Language" content="en" />
		<title>Dreamsplan</title>
		<link href="calendar.css" type="text/css" media="all" rel="stylesheet" />
		<link href="style.css" type="text/css" media="all" rel="stylesheet" />
		<link rel="shortcut icon" href="img/dreamsplan.ico">
		
        <meta name="robots" content="index,follow">
		<meta name="subject" content="Dreamsplan" />
		<meta name="description" content="Simplest online TASK MANAGEMENT and PLANNING tool - single click interface, superfast, no registration, touch supported" />
		<meta name="keywords" content="todo, list, todolist, task, time,  goal, project, management, manager, free, tool, online" />
		
		<script src="scripts/calendar.js" type="text/javascript"></script>
  		<script src="scripts/core.js" type="text/javascript"></script>
  		<script src="scripts/tasklists.js" type="text/javascript"></script>
  		<script src="scripts/timeline.js" type="text/javascript"></script>
  		<!--[if IE]>
  			<script src="scripts/calendar.vbs" type="text/vbscript"></script>
  			<script type="text/javascript">
			    calendar.FirstDayIndex = vbsFirstDayOfWeekIsMon() ? 1 : 0;
			</script>
		<![endif]-->
  		<!--[if IE 9]>
			<script type="text/javascript">
			    tasklists.EnableLayoutCorrection = true;
			</script>
		<![endif]-->

		<script type="text/javascript">
		  var _gaq = _gaq || [];
		  _gaq.push(['_setAccount', 'UA-33394528-1']);
		  _gaq.push(['_trackPageview']);

		  (function() {
		    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  })();
		</script>
	</head>
	<body>
		<div id="menu">
		    <h2><img src="img/dreamsplan.png" alt="dreamsplan logo" /></h2>
			<?php if ($_GET['view'] != 'notsupported') { ?>
				<!--[if lt IE 9]>
					<script type="text/javascript">
			        	document.location.href += '&view=notsupported';
		         	</script>
				<![endif]-->
				<!--
				<div id="menuItems">
					<a onmousedown="tasklists.SetMode(this, 'tasklists')" onfocus="this.blur()" href="javascript:void(0)" class="active">task lists</a>
					<a onmousedown="tasklists.SetMode(this, 'timeline')" onfocus="this.blur()" href="javascript:void(0)">timeline</a>
				</div>
				-->
				<div id="paginator" style="display: none">
                	<a onfocus="this.blur()" onmousedown="tasklists.ShowUserSettings(this)" href="javascript:void(0)">settings</a>
					<a onfocus="this.blur()" id="showall" href="javascript:void(0)" onmousedown="tasklists.SetShowAll(this)" class="active">show all</a>
					<a onfocus="this.blur()" id="today" href="javascript:void(0)" onmousedown="this.className = 'active'; tasklists.LoadTaskLists()">today</a>
					<a onfocus="this.blur()" class="navigation left" href="javascript:tasklists.ShiftDate(-1)"><img src="img/left.png" alert="left" /></a>
					<a onfocus="this.blur()" id="datename" href="javascript:void(0)" onmousedown="tasklists.SelectTimeFrame(this)">?</a>
					<a onfocus="this.blur()" class="navigation right" href="javascript:tasklists.ShiftDate(1)"><img src="img/right.png" alert="right" /></a>
				</div>
			<?php } ?>
		</div>
		<div id="contents">
		    <?php echo $view->GetContents(Session::$SessionId); ?>
		</div>
		<?php
            if ($_GET['view'] != 'notsupported')
			{
			    if (!Session::$CookieSet)
				{
	    			if (Session::$SessionId == 'beta')
					{
						$msg  = '<h2>Welcome to Dreamsplan!</h2>';
						$msg .= '<h3>...the easiest way to manage your tasks and plan your dreams.</h3>';
						$msg .= '<p>This is a preview of Dreamsplan offering a public account where anyone can contribue. You can create your own private account int the \'settings\' menu.</p>';
						Controls::PrintWindow('welcome', $msg);
					}
					else
					{
						$msg  = '<h2>Welcome to Dreamsplan!</h2>';
						$msg .= '<h3>...the easiest way to manage your tasks and plan your dreams.</h3>';
						$msg .= '<p>This is your private account at Dreamsplan which is anonymous and accessible only to you. Don\'t forget to save its address to access the account later and assign an email for its recovery.</p>';
						Controls::PrintWindow('welcome', $msg);
					}
				}
                echo '<div id="credits">icons provided under <a href="http://creativecommons.org/licenses/by/3.0/deed.en">CC-BY license</a> by <a href="http://glyphicons.com">glyphicons.com</a></div>';
			}
		?>
      	<form id="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick"/>
			<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----"/>
			<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"/>
			<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1"/>
		</form>
	</body>
</html>
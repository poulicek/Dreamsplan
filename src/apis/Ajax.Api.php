<?php
if (!isset($_REQUEST['m']))
	return;
	
chdir('../');

require_once('views/TaskLists.View.php');
$view = new TaskLists();

switch ($_REQUEST['m'])
{
	case 'GetTaskLists' :
	    exit($view->GetTaskLists($_REQUEST['a'], $_REQUEST['d'], $_REQUEST['t'], $_REQUEST['o'], $_REQUEST['w'], $_REQUEST['c']));
	    
	case 'AddTaskList' :
	    exit($view->AddTaskList($_REQUEST['a']));

	case 'SetTaskListTitle' :
	    exit($view->SetTaskListTitle($_REQUEST['a'], $_REQUEST['i'], $_REQUEST['v']));

	case 'SetTaskTitle' :
	    exit($view->SetTaskTitle($_REQUEST['a'], $_REQUEST['i'], $_REQUEST['v']));
	    
	case 'AddTask' :
		exit($view->AddTask($_REQUEST['a'], $_REQUEST['i'], $_REQUEST['v'], $_REQUEST['s']));
		
	case 'SetTaskState' :
		exit($view->SetTaskState($_REQUEST['a'], $_REQUEST['i'], $_REQUEST['s']));

	case 'GetSettings' :
		exit($view->GetSettings($_REQUEST['a'], $_REQUEST['i']));
		
	case 'SetCategory' :
		exit($view->SetCategory($_REQUEST['a'], $_REQUEST['i'], $_REQUEST['c']));
		
	case 'RenewList' :
		exit($view->RenewList($_REQUEST['a'], $_REQUEST['i']));
		
	case 'SetListOrder' :
		exit($view->SetListOrder($_REQUEST['a'], $_REQUEST['l'], $_REQUEST['i']));
		
	case 'SetMinimized' :
		exit($view->SetMinimized($_REQUEST['a'], $_REQUEST['i'], $_REQUEST['v']));

	case 'SetAllMinimized' :
		exit($view->SetAllMinimized($_REQUEST['a'], $_REQUEST['v']));
		
	case 'SetDeadline' :
	    exit($view->SetDeadline($_REQUEST['a'], $_REQUEST['i'], intval($_REQUEST['v'])));
	    
	case 'SetStart' :
	    exit($view->SetStart($_REQUEST['a'], $_REQUEST['i'], intval($_REQUEST['v'])));

	case 'ShiftTask' :
	    exit($view->ShiftTask($_REQUEST['a'], $_REQUEST['i'], intval($_REQUEST['v'])));

	case 'DeleteTaskList' :
	    exit($view->DeleteTaskList($_REQUEST['a'], $_REQUEST['i']));

	case 'DeleteTask' :
	    exit($view->DeleteTask($_REQUEST['a'], $_REQUEST['i']));

	case 'GetUserSettings' :
    	exit($view->GetUserSettings($_REQUEST['a']));

	case 'RecoverAccount' :
    	require_once('views/Settings.View.php');
		exit(Settings::RecoverAccount($_REQUEST['v']));

	case 'AssignEmail' :
    	require_once('views/Settings.View.php');
		exit(Settings::AssignEmail($_REQUEST['a'], $_REQUEST['v']));

	default :
	    exit('Unsupported method '.$_REQUEST['m']);
}

?>

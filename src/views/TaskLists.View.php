<?php
require_once('IView.php');
require_once('./helpers/Storage.Helper.php');

class TaskLists implements IView
{
	private $serverTmOffset = 1;
	private $allShown = false;
	private $listedTags = array();

	private $dayEnd;


	public function CompareTasks($a, $b)
	{
		if ($a['Start'] < $this->dayEnd && $b['Start'] < $this->dayEnd)
    	return !empty($a['Deadline']) && (empty($b['Deadline']) || $a['Deadline'] < $b['Deadline']) ? -1 : 1;

		return $a['Start'] < $b['Start'] ? -1 : 1;
	}


	public function GetContents($account)
	{
	    //Storage::CorrectDataSet($account);
	    return '<div class="message">loading task lists...</div><script type="text/javascript">window.onload = function() { tasklists.Initialize("'.$account.'"); }</script>';
	}
	
	
	public function AddTaskList($account)
 	{
	    $data = Storage::LoadUserData($account);

	    $data['Lists'][] = array('Id' => dechex(time()), 'Name' => '', 'Category' => 0, 'Tasks' => array(), 'Minimized' => 0);
	    Storage::SaveUserData($account, $data);
	}
	
	
	public function GetTaskLists($account, $dayOffset, $hourOffset, $mode = 'tasklists', $screenWidth = null, $weekCount = 1)
	{
    	$html = '';
		$hourOffset = $this->serverTmOffset - $hourOffset;
	    $date = getdate(time() + (24 * $dayOffset + $hourOffset) * 3600);
	    $time = mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']) - $hourOffset * 3600;
			$this->dayEnd = $time + 86400;

		$cnt = $this->getTimeLineBody($account, $dayOffset, $date, $time, $screenWidth, $weekCount);
		if (!empty($cnt))
			$html  .= '<div class="frame">'.$cnt.'</div>';

		$cnt = $this->getTaskBoardBody($account, $dayOffset, $date, $time);
		if (!empty($cnt))
			$html  .= '<div class="frame">'.$cnt.'</div>';

		return $html;
	}


	private function getTimeLineBody($account, $dayOffset, $date, $time, $screenWidth, $weekCount)
	{
		$data = Storage::LoadUserData($account);
	    require_once('TimeLine.View.php');
	    $tl = new TimeLine();
		$cnt = $tl->GetTimeLine($data['Lists'], $date, $time, $dayOffset, $screenWidth, $weekCount);

		return empty($cnt) ? null : '<span id="selecteddate">'.date('Y/m/d', $date[0]).'</span>'.$cnt;
	}


	private function getTaskBoardBody($account, $dayOffset, $date, $time)
	{
		$data = Storage::LoadUserData($account);
		$hotList = $this->getHotTasksListHtml($data, $time, $dayOffset);
		$listsHtml = '';
		$this->allShown = true;
	    foreach ($data['Lists'] as $list)
	        $listsHtml .= $this->getTaskListHtml($list, $time, $dayOffset);
	    $listsHtml .= $this->getEmptyListHtml();

		$html  = '<span id="selecteddate">'.date('Y/m/d', $date[0]).'</span>';
		$html .= '<span id="allShownValue">'.($this->allShown ? 1 : 0).'</span>';
		$html .= $this->getTagBar($this->listedTags);
		$html .= '<div id="board">'.$hotList.$listsHtml;
		$html .= '<div class="clear"></div></div>';
		$html .= $this->getStateMenu();

		return $html;
	}


	public function DeleteTaskList($account, $listId)
	{
	    $data = Storage::LoadUserData($account);
        foreach ($data['Lists'] as $key => $list)
        {
	        if ($list['Id'] == $listId)
            {
				unset($data['Lists'][$key]);
	        	Storage::SaveUserData($account, $data);
            	return;
	        }
	    }
	}
	
	
	public function SetTaskListTitle($account, $listId, $value)
	{
	    $value = strip_tags($value);
	    $data = Storage::LoadUserData($account);
	    
	    foreach ($data['Lists'] as $key => $list)
	    {
	        if ($list['Id'] == $listId)
	        {
	            if (!trim($value))
	                unset($data['Lists'][$key]);
	            else
		        	$data['Lists'][$key]['Name'] = $value;
	        	Storage::SaveUserData($account, $data);
	        	return;
	        }
	    }
	}
	
	
	public function SetTaskTitle($account, $taskId, $value)
	{
	    $data = Storage::LoadUserData($account);
	    foreach ($data['Lists'] as $lkey => $list)
	    {
      		foreach ($list['Tasks'] as $tkey => $task)
      		{
      			if ($task['Id'] == $taskId)
        		{
        			$value = $this->createHypertextLinks(trim(strip_tags($value, '<br>')));
        			$value = $this->detectTags($value, $tags);
        			
					if (!trim(strip_tags($value)))
	                	unset($data['Lists'][$lkey]['Tasks'][$tkey]);
	            	else
	            	{
		        		$data['Lists'][$lkey]['Tasks'][$tkey]['Title'] = $value;
		        		$data['Lists'][$lkey]['Tasks'][$tkey]['Tags'] = $tags;
		        	}
		        		
		        	Storage::SaveUserData($account, $data);
		        	return;
        		}
        	}
	    }
	}
	
	
	public function AddTask($account, $listId, $value, $start = null)
	{
  		$value = explode('|', strip_tags(str_ireplace(array('<P', '<DIV', '<TR'), array('|<P', '|<DIV', '|<TR'), $value), '<br>'));
	    $data = Storage::LoadUserData($account);

	    foreach ($data['Lists'] as $key => $list)
	    {
	        if ($list['Id'] == $listId)
	        {
	            $time = time();
	    		foreach ($value as $val)
	    		{
	    		    if ($val)
	    		    {
	    		        $val = $this->createHypertextLinks(trim(strip_tags($val, '<br>')));
        				$val = $this->detectTags($val, $tags);
						$data['Lists'][$key]['Tasks'][] = array('Id' => dechex(++$time), 'Title' => $val, 'State' => 0, 'End' => null, 'Start' => $start, 'Assigned' => $time, 'Tags' => $tags);
					}
				}
	        			
	        	Storage::SaveUserData($account, $data);
	        	return;
	        }
	    }
	}
	
	
	public function SetTaskState($account, $taskId, $state)
	{
		$data = Storage::LoadUserData($account);
	    foreach ($data['Lists'] as $lkey => $list)
	    {
      		foreach ($list['Tasks'] as $tkey => $task)
      		{
      			if ($task['Id'] == $taskId)
        		{
		        	$data['Lists'][$lkey]['Tasks'][$tkey]['State'] = $state;

					if ($state == 0)
					{
						$data['Lists'][$lkey]['Tasks'][$tkey]['End'] = null;
						$data['Lists'][$lkey]['Tasks'][$tkey]['Start'] = $data['Lists'][$lkey]['Tasks'][$tkey]['Assigned'];
					}
					else if ($state == 1)
					{
						$data['Lists'][$lkey]['Tasks'][$tkey]['End'] = null;
		        		$data['Lists'][$lkey]['Tasks'][$tkey]['Start'] = time();
		        	}
		        	else if ($state > 1)
		        	{
		        		$data['Lists'][$lkey]['Tasks'][$tkey]['End'] = time();
		        		$data['Lists'][$lkey]['Tasks'][$tkey]['Start'] = min($data['Lists'][$lkey]['Tasks'][$tkey]['Start'], $data['Lists'][$lkey]['Tasks'][$tkey]['End']);
		        	}

		        	Storage::SaveUserData($account, $data);
		        	return;
        		}
        	}
	    }
	}


	public function DeleteTask($account, $taskId)
	{
		$data = Storage::LoadUserData($account);
	    foreach ($data['Lists'] as $lkey => $list)
	    {
      		foreach ($list['Tasks'] as $tkey => $task)
      		{
      			if ($task['Id'] == $taskId)
        		{
					unset($data['Lists'][$lkey]['Tasks'][$tkey]);
		        	Storage::SaveUserData($account, $data);
		        	return;
        		}
        	}
	    }
	}
	
	
	public function SetCategory($account, $listId, $category)
	{
		$data = Storage::LoadUserData($account);
		foreach ($data['Lists'] as $key => $list)
		{
	        if ($list['Id'] == $listId)
	        {
            	$data['Lists'][$key]['Category'] = $category;
              	Storage::SaveUserData($account, $data);
		       	return;
           	}
        }
	}
	
	
	public function GetSettings($account, $listId)
	{
		$data = Storage::LoadUserData($account);
		foreach ($data['Lists'] as $key => $list)
	    {
	        if ($list['Id'] == $listId)
	        {
				$html = '<div class="settings">';
				$html .= '<label>Category:</label>';

				$html .= '<div class="colors">';
				for ($i = 0; $i < 6; $i++)
					$html .= '<a class="cat'.$i.($list['Category'] == $i ? ' selected' : '').'" href="javascript:tasklists.SetCategory(\''.$listId.'\', '.$i.')"></a>';
				$html .= '</div>';
				$html .= '<a href="javascript:tasklists.RenewTaskList(\''.$listId.'\')" class="button">renew task list</a>';
                $html .= '<a href="javascript:tasklists.DeleteTaskList(\''.$listId.'\')" class="button">delete task list</a>';
				$html .= '<div class="clear"></div></div>';

				return $html;
			}
		}
	}
	
	
	public function RenewList($account, $listId)
	{
		$data = Storage::LoadUserData($account);
	    foreach ($data['Lists'] as $lkey => $list)
	    {
	    	if ($list['Id'] == $listId)
	        {
	      		foreach ($list['Tasks'] as $tkey => $task)
	      		{
		        	$data['Lists'][$lkey]['Tasks'][$tkey]['State'] = 0;
		        	$data['Lists'][$lkey]['Tasks'][$tkey]['Start'] = null;
		        	$data['Lists'][$lkey]['Tasks'][$tkey]['End'] = null;
	        	}

				Storage::SaveUserData($account, $data);
			    return;
        	}
	    }
	}
	
	
	public function SetMinimized($account, $listId, $minimized)
	{
		$data = Storage::LoadUserData($account);
		if ($listId == 'hotlist')
		{
		    $data['MinimizeHotlist'] = $minimized;
		    Storage::SaveUserData($account, $data);
		}
		else
		{
		    foreach ($data['Lists'] as $lkey => $list)
		    {
		    	if ($list['Id'] == $listId)
		        {
			        $data['Lists'][$lkey]['Minimized'] = $minimized;
					Storage::SaveUserData($account, $data);
				    return;
	        	}
		    }
	    }
	}
	

	public function SetAllMinimized($account, $minimized)
	{
		$data = Storage::LoadUserData($account);
	    foreach ($data['Lists'] as $lkey => $list)
		    $data['Lists'][$lkey]['Minimized'] = $minimized;
	    Storage::SaveUserData($account, $data);
	}
	
	
	public function SetDeadline($account, $taskId, $value)
	{
		$data = Storage::LoadUserData($account);
	    foreach ($data['Lists'] as $lkey => $list)
	    {
      		foreach ($list['Tasks'] as $tkey => $task)
      		{
      			if ($task['Id'] == $taskId)
        		{
		        	$data['Lists'][$lkey]['Tasks'][$tkey]['Deadline'] = $value ? $value : null;
		        	if ($value && $value < $data['Lists'][$lkey]['Tasks'][$tkey]['Start'])
		        		$data['Lists'][$lkey]['Tasks'][$tkey]['Start'] = $value;
		        		
		        	Storage::SaveUserData($account, $data);
		        	return;
        		}
        	}
	    }
	}
	
	
	public function SetStart($account, $taskId, $value)
	{
		$data = Storage::LoadUserData($account);
	    foreach ($data['Lists'] as $lkey => $list)
	    {
      		foreach ($list['Tasks'] as $tkey => $task)
      		{
      			if ($task['Id'] == $taskId)
        		{
		        	$data['Lists'][$lkey]['Tasks'][$tkey]['Start'] = $value ? $value : null;
		        	if ($value && $value > $data['Lists'][$lkey]['Tasks'][$tkey]['Deadline'])
		        		$data['Lists'][$lkey]['Tasks'][$tkey]['Deadline'] = $value;

		        	Storage::SaveUserData($account, $data);
		        	return;
        		}
        	}
	    }
	}
	
	
	public function ShiftTask($account, $taskId, $value)
	{
	    if (!$value)
	        return;
	        
		$data = Storage::LoadUserData($account);
	    foreach ($data['Lists'] as $lkey => $list)
	    {
      		foreach ($list['Tasks'] as $tkey => $task)
      		{
      			if ($task['Id'] == $taskId)
        		{
        		    if ($data['Lists'][$lkey]['Tasks'][$tkey]['Start'])
		        		$data['Lists'][$lkey]['Tasks'][$tkey]['Start'] += $value;

					if ($data['Lists'][$lkey]['Tasks'][$tkey]['Deadline'])
		        		$data['Lists'][$lkey]['Tasks'][$tkey]['Deadline'] += $value;

		        	Storage::SaveUserData($account, $data);
		        	return;
        		}
        	}
	    }
	}
	
	
	public function SetListOrder($account, $listId, $index)
	{
	    $data = Storage::LoadUserData($account);
	    
	    // checking the array boundaries
	    if ($index < 0 || $index > count($data['Lists']) - 1)
	        return;
	    
	    $source = array();
	    $result = array();
	    $pickedList = null;

	    // picking the right item
	    foreach ($data['Lists'] as $lkey => $list)
	    {
	    	if ($list['Id'] != $listId)
	    	{
	    		$source[] = $list;
	    		continue;
	    	}
	    	
	    	if ($lkey == $index)
            	return;
            	
            $pickedList = $list;
	    }

	    if (!$pickedList)
	    	return;
	    	
	    // inserting picket list on the right position
	    foreach ($source as $lkey => $list)
	    {
	    	if ($lkey == $index)
			{
	    	    $result[] = $pickedList;
            	$pickedList = null;
			}
	    	$result[] = $list;
	    }

		if ($pickedList)
			$result[] = $pickedList;

	    $data['Lists'] = $result;
	    Storage::SaveUserData($account, $data);
	}
	

	public function GetUserSettings($account)
	{
		require_once('Settings.View.php');
		$s = new Settings();
		return $s->GetContents($account);
	}

	
	private function getTaskListHtml($list, $time, $dayOffset)
	{
		$html = '';
	    $minimized = $list['Minimized'];
		        
	    if ($minimized)
	    {
	    	$this->allShown = false;
	    	$html .= '<div class="placeholder minimized" onclick="tasklists.SetMinimized(\''.$list['Id'].'\', 0)">';
		    $html .= '<div class="taskList cat'.$list['Category'].'">';
		    $html .= '<h3><span>';
			$html .= $list['Name'] ? $list['Name'] : 'unnamed goal';
			$html .= '</span></h3>';
	    }
	    else
	    {
	    	$html .= '<div class="placeholder"><del></del>';
		    $html .= '<div id="'.$list['Id'].'" class="taskList cat'.$list['Category'].'">';
		    $html .= '<h3 class="movable">';
			$html .= '<span id="SetTaskListTitle&i='.$list['Id'].'" class="editable'.($list['Name'] ? '' : ' empty').'">'.($list['Name'] ? $list['Name'] : 'unnamed goal').'</span>';

			if (!$list['Name'])
				$html .= '<a class="tool" href="javascript:tasklists.DeleteTaskList(\''.$list['Id'].'\')"><img src="img/delete.png" alt="delete"/></a>';
			else
			{
				$html .= '<a class="tool" href="javascript:tasklists.SetMinimized(\''.$list['Id'].'\', 1)"><img src="img/minimize.png" alt="minimize"/></a>';
            	$html .= '<a class="tool" href="javascript:tasklists.LoadSettings(\''.$list['Id'].'\')"><img src="img/config.png" alt="config"/></a>';
			}
			
			$html .= '</h3>';
	     	$html .= '<div class="taskListBody">';
	     	$html .= '<table>';
	     	
	     	$tm1 = $time;
	     	$tm2 = $this->dayEnd;

		    usort($list['Tasks'], array("TaskLists", 'CompareTasks'));

		    foreach ($list['Tasks'] as $task)
		        if (($task['Assigned']) < $tm2 &&
					(($task['State'] < 2 && ($dayOffset >= 0 || $task['Start'] < $tm2) && ($dayOffset <= 0 || empty($task['Deadline']) || $task['Deadline'] >= $tm1)) || $task['End'] >= $tm1))
		        	$html .= '<tr><td>'.$this->getTaskHtml($task, $time).'</tr></td>';
		    
		    $html .= '</table>';
			if ($dayOffset >= 0)
	    		$html .= $this->getEmptyTaskHtml($list['Id']);
		    $html .= '<div class="clear"></div></div>';
	    }
	    
	    $html .= '</div></div>';
	    return $html;
	}
	
	
	private function isTaskListEmpty($list, $time)
	{
	    foreach ($list['Tasks'] as $task)
	        if ($task['Assigned'] <= $time)
	            return false;

		return true;
	}
	
	
	private function getHotTasksListHtml(&$data, $time, $dayOffset)
	{
		$tm1 = $time;
	    $tm2 = $this->dayEnd;

        $done = 0;
		$total = 0;
        $urgents = 0;
		$tasks = '';
		$hottasks = array();
		$visible = !isset($data['MinimizeHotlist']) || !$data['MinimizeHotlist'];
		
		foreach ($data['Lists'] as $list)
		{
			foreach ($list['Tasks'] as $task)
			{
                $urgent = !empty($task['Deadline']) && $task['Deadline'] < $tm2;

			    if (($task['State'] < 2 && $task['Start'] < $tm2 && !empty($task['Deadline']) && $task['Deadline'] >= $tm1)
					|| (($end = $task['End']) && $end >= $tm1 && $end < $tm2)
					|| (!$end && $urgent && ($dayOffset <= 0 || $task['Deadline'] >= $tm1)))
			    {
                    $done += $task['State'] > 1 ? 1 : 0;
                    $urgents += $urgent ? 1 : 0;
                    $total++;

			        if ($visible)
					{
              			$task['List'] = $list;
                    	array_push($hottasks, $task);
					}
			    }
	        }
		}

		if (!$total)
		    return;

		usort($hottasks, array("TaskLists", 'CompareTasks'));
		foreach ($hottasks as $task)
		{
			$list = $task['List'];

			$tasks .= '<tr><td>';
			$tasks .= $this->getTaskHtml($task, $time, '&h');
			$tasks .= $list['Name'] ? '<a class="tag listTag cat'.$list['Category'].'" href="javascript:tasklists.ShowSingleList(\''.$list['Id'].'\')">'.$list['Name'].'</a>' : '';
			$tasks .= '</tr></td>';
		}

		if (!$visible)
		{
	    	$html  = '<div id="hotList" class="placeholder minimized" onclick="tasklists.SetMinimized(\'hotlist\', 0)">';
		    $html .= '<div class="taskList">';
		    $html .= '<h3>';
			$html .= '<span>Hot Tasks</span><span class="metrics">'.($urgents > 0 ? intval(100 * ($done / $urgents)) : 0).'%</span>';
			$html .= '</h3>';
		}
		else
		{
		    $html  = '<div id="hotList" class="placeholder"><del></del>';
		    $html .= '<div class="taskList">';
		    $html .= '<h3>';
			$html .= '<span>Hot Tasks</span><span class="metrics">'.($urgents > 0 ? intval(100 * ($done / $urgents)) : 0).'%</span>';
	    	$html .= '<a class="tool" href="javascript:tasklists.SetMinimized(\'hotlist\', 1)"><img src="img/minimize.png" alt="minimize"/></a>';
			$html .= '</h3>';
	     	$html .= '<div class="taskListBody">';
	     	$html .= '<table>';
	     	$html .= $tasks ? $tasks : '<tr><td class="emptyHotList"><div class="task"><span class="taskTitle">no hot tasks</span></div></td></tr>';
	     	$html .= '</table></div>';
     	}
     	
     	$html .= '</div></div>';
     	return $html;
	}
	
	
	private function getTaskHtml($task, $time, $param = null)
	{
		$tagsHtml = '';
		$className = '';
    	$deadlineHtml = '';
		$state = $task['State'];

		if ($task['End'] > $this->dayEnd)
		{
			if ($state >= 2)
				$state = 1;
		}
		else if ($state < 2)
		{
    		$deadlineHtml = $this->getDeadlineMarker($task, $time, $param);
			if ($task['Start'] > $this->dayEnd)
        		$className .= ' futureTask';
			else
			{
        		$className .= ' #todo';
				if ($state == 0)
					$className .= ' todoNow';
			}
		}

		if (!empty($task['Tags']))
		{
			foreach ($task['Tags'] as $tag)
			{
				$className .= ' '.$tag;
				$t = substr($tag, 1);
				$this->listedTags[$t] = isset($this->listedTags[$t]) ? $this->listedTags[$t] + 1 : 1;
				$tagsHtml .= '<a class="tag" href="javascript:tasklists.SetTag(\''.$tag.'\')">'.$t.'</a>';
			}
	    }

		//$markers = array('', '', '', '');
	    $markers = array('<img src="img/dot.png" alt="pending" />', '<img src="img/progress.png" alt="progress" />', '<img src="img/accept.png" alt="accept" />', '<img src="img/cancel.png" alt="cancel" />');
	    $html 	= '<ins class="task'.($state >= 2 ? ' accomplished' : '').($state == 3 ? ' cancelled' : '').$className.'">';
	    $html .= $deadlineHtml;
 		$html .= '<a href="javascript:void()" onmousedown="tasklists.SwitchTaskState(this, '.$state.', \''.$task['Id'].'\'); return false" class="status">'.$markers[$state].'</a>';
    	$html .= '<span class="taskTitle'.($state < 2 ? ' editable' : '').'" title="'.strip_tags($task['Title']).'" id="SetTaskTitle&i='.$task['Id'].$param.'">'.$task['Title'].'</span>';
     	$html .= $tagsHtml;
		$html .= $param ? '' : '<a class="taskDelete" href="javascript:void(0)" onmousedown="tasklists.DeleteTask(\''.$task['Id'].'\')"><img src="img/delete.png" alt="delete" /></a>';
	    $html .= '<span class="clear">&nbsp;</span></ins>';
	    return $html;
	}
	

	private function getEmptyTaskHtml($listId)
	{
	    $html  = '<div class="task emptyTask">';
        $html .= '<ins class="#todo">';
	    $html .= '<span class="status">+</span>';
	    $html .= '<span id="AddTask&i='.$listId.'" class="taskTitle editable empty">new task</span>';
	    $html .= '</ins></div>';
	    return $html;
	}
	

	private function getDeadlineMarker(&$task, $time, $param = null)
	{
	  	$deadline = empty($task['Deadline']) ? 0 : $task['Deadline'];
		$daysleft = $deadline ? round(($deadline - $time) /  86400) : null;
	  	$opacity = is_null($daysleft) ? 0 : max(1, (8 - max(0, $daysleft))) / 8;
	    
	  	$html  = '<a href="javascript:void(0)" onmousedown="tasklists.SelectDeadline(this, \''.$task['Id'].$param.'\', \''.($deadline ? date('Y/m/d', $deadline) : '').'\'); if (event) event.stopPropagation();" class="deadline'.(is_null($daysleft) ? ' emptyDeadline' : '').'" title="set deadline">';
		$html .= '<div class="value"'.($opacity > 0.5 ? ' style="color: white"' : '').'">'.(is_null($daysleft) ? '<img src="img/progress.png" alt="progress" />' : (abs($daysleft) > 99 ? '!' : $daysleft)).'</div>';
		$html .= '<div class="hotness" style="opacity: '.$opacity.'"></div>';
		$html .= '</a>';

		return $html;
	}
	
	
	private function getEmptyListHtml()
	{
		$html = '<a onfocus="this.blur()" href="javascript:tasklists.AddTaskList()" class="emptyList taskList">';
		$html .= '+';
		$html .= '</a>';
		return $html;
	}
	
	
	private function getStateMenu()
	{
		$html = '<div id="stateMenu">';
		$html .= '<a href="javascript:tasklists.void(0)" rel="2"><img src="img/accept.png" alt="accept" /> accomplished</a>';
		$html .= '<a href="javascript:tasklists.void(0)" rel="1"><img src="img/progress.png" alt="progress" /> in progress</a>';
  		$html .= '<a href="javascript:tasklists.void(0)" rel="0"><img src="img/dot.png" alt="pending" /> pending</a>';
  		$html .= '<a href="javascript:tasklists.void(0)" rel="3"><img src="img/cancel.png" alt="delete" /> cancelled</a>';
		$html .= '<div class="clear"></div>';
		$html .= '</div>';
		return $html;
	}
	
	
	private function getTagBar($tags)
	{
	    $tagsHtml = '';
		foreach ($tags as $tag => $val)
		{
		    if ($val)
	    		$tagsHtml .= '<a href="javascript:void(0)" onclick="tasklists.SwitchTag(this);" onfocus="this.blur()">'.$tag.'</a>';
		}
		    
	    $html  = '<div id="tagBarHolder"><div id="tagBar">';
      	$html .= '<div'.(empty($tags) ? '' : ' class="mainTags"').'>';
	    $html .= '<a href="javascript:void(0)" onclick="tasklists.SwitchTag(this)" class=" active" onfocus="this.blur()">all tasks</a>';
      	$html .= '<a href="javascript:void(0)" onclick="tasklists.SwitchTag(this)" onfocus="this.blur()">todo</a>';
		$html .= '</div>';
	    $html .= $tagsHtml;
	    $html .= '</div><div class="clear"></div></div>';
	    
	    return $html;
	}
	
	
	private function createHypertextLinks($str)
	{
	    // http(s) replacement
	    $str = preg_replace('/(http[s]?:\/\/)([^\/?]+)([^\s,;()]*)/', '<a target="_blank" href="$1$2$3">$2</a>', $str);
	    
	    // www replacement
	    $str = preg_replace('/([^>\/])(www[.][^\/?]+)([^\s,;()]*)/', '$1<a target="_blank" href="http://$2$3$4">$2</a>', $str);
	    
		// email replacement
	    $str = preg_replace('/(^|\s)([^\s<>:]+[@][^\s,:;()]+)/', '$1<a target="_blank" href="mailto:$2">$2</a>', $str);
	    
	    return $str;
	}
	
	
	private function detectTags($str, &$tags)
	{
	    preg_match_all('/\s(#[^\s]+)/', $str, $matches);
	    $str = trim(preg_replace('/\s(#[^\s]+)/', '', $str));

	    if ($str && count($matches) == 2)
	    {
	    	foreach ($matches[1] as $match)
	    	{
	        	$str .= '<span class="tagText"> '.$match.'</span>';
	        	$tags[] = $match;
	        }
	    }
		return $str;
	}
}
?>

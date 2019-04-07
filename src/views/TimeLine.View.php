<?php
require_once('IView.php');
require_once('./helpers/Storage.Helper.php');

class TimeLine implements IView
{
	private $cellWidth = 24;
	private $cellHeight = 24;
	private $days = 30;

	public function GetContents($account)
	{
	    return '<div class="message">loading timeline...</div>';
	}


	public function GetTimeLine(&$lists, $date, $time, $dayOffset, $screenWidth, $weekCount)
	{
		$weekDay = $date['wday'] == 0 ? 7 : $date['wday'];
		$this->days = $weekCount * 7 + (7 - $weekDay + 1);
		$this->cellWidth = intval($screenWidth / $this->days);

		$sortedTasks = $this->getSortedTasks($lists, $time, $dayOffset);
  		$grid =  $this->getTaskGrid($sortedTasks, $weekDay, $time, false);
		if (!$grid)
			return null; //$rows = '<tr><td class="message" colspan="'.$this->days.'">No tasks to display.</td></tr>';

		$html  = '<div id="timeline" onmousedown="timeline.MoveTimeline(event); return false"><table class="movable">';
		$html .= $this->getHeadings($weekDay, $time, $dayOffset);
		$html .= $grid;
		$html .= '</table></div>';
		return $html;
	}
	
	
	private function getSortedTasks(&$lists, $time, $dayOffset)
	{
    	$tasks = array();
		$endtime = $time + $this->days * 86400;

		foreach ($lists as $list)
		{
		    if ($list['Minimized'])
		        continue;
		        
			foreach ($list['Tasks'] as $task)
			{
				// skipping the task according to their state
				if ($task['State'] == 3 || $task['State'] > 1)
					continue;

				// correction of deadline of tasks which already ended
				if (!empty($task['End']))
        			$task['Deadline'] = $task['End'];

				// skipping the tasks from the past
				if (empty($task['Deadline']) || $task['Deadline'] < $time)
					continue;

				// overdated tasks
				//if ($task['State'] < 2 && $dayOffset <= 0 && $task['Deadline'] < $time)
				//	$task['Deadline'] = $time;

				// validation of time frame
				if ($task['Start'] > $endtime || $task['Deadline'] < $time)
				    continue;

				$listId = $list['Id'];
		    	$task['ListId'] = $listId;
		        $task['ListCategory'] = $list['Category'];
		        
				if (!isset($tasks[$listId]))
					$tasks[$listId] = array();

				$tasks[$listId][date('Ymd', $task['Start']).date('Ymd', $task['Deadline']).$list['Category'].(3 - $task['State']).$task['Id']] = $task;
			}
		}

		// flattening the array
		foreach ($tasks as $listId => $sorted)
		{
        	ksort($sorted);
			$tasks[$listId] = array_values($sorted);
		}

		return $tasks;
	}
	
	
	private function getHeadings($weekDay, $time, $dayOffset)
	{
		$html = '<thead><tr class="weekHeadings">';
		for ($i = 0; $i < $this->days; $i++)
		{
        	$dayIdx = ($weekDay + $i) % 7;
			$span = $dayIdx == 1 ? min(7, $this->days - $i) : 7 - $weekDay + 1;

		    if ($span)
		    {
		    	$i += $span - 1;
				$html .= '<th colspan="'.$span.'">'.($span > 3 ? date('j M', $time + 86400 * ($i - $span + 1)) : '').'</th>';
			}
		}
		
		$html .= '</tr><tr class="dayHeadings">';
		for ($i = 0; $i < $this->days; $i++)
	    {
	        $class = $this->getClassName($weekDay, $i).($i == -$dayOffset ? ' today' : '');
	        $html .= '<th class="'.$class.'" title="'.date('D M d Y', $time).'"><a href="javascript:tasklists.SetDate(\''.date('Y/m/d', $time).'\')">'.substr(date('D', $time), 0, 2).'</th>';
	        $time += 86400;
	    }
	    
	    $html .= '</tr></thead>';
	    return $html;
	}
	
	
	private function getTaskGrid(&$tasks, $weekDay, $time, $placeTasks)
	{
     	$html = '';
		while (!empty($tasks))
		{
        	$i = 0;
        	$html .= $placeTasks ? '<tr>' : '';

			foreach ($tasks as $key => $task)
			{
				if (!$placeTasks)
					$html .= '<tbody>'.$this->getTaskGrid($task, $weekDay, $time, true).'</tbody>';
				else
				{
	            	$startIdx = intval(($task['Start'] - $time) / 86400);
	                $endIdx = intval(($task['Deadline'] - $time) / 86400);

					// skpping the tasks which can't be placed in the current row
					if ($i > 0 && $startIdx <= $i)
						continue;

					$on = $task['State'] < 2;
						$html .= $this->createGrid($i, $startIdx - ($on ? 1 : 0), $weekDay);
						$html .= $this->placeTask($i, $startIdx, $endIdx, $task, $weekDay, $on);

				}
				unset($tasks[$key]);
			}

			$html .= $placeTasks ? $this->createGrid($i, $this->days, $weekDay).'</tr>' : '';
		}

	    return $html;
	}


	private function getClassName($weekDay, $i)
	{
		$dayIdx = ($weekDay + $i) % 7;
		$class  = $dayIdx == 0 || $dayIdx == 6 ? ' weekend' : '';
		$class .= $dayIdx == 0 ? ' endofweek' : '';

		return $class;
	}


	private function placeTask(&$i, $startIdx, $endIdx, &$task, $weekDay, $on)
	{
    	$html = '';
		$taskId = $task['Id'];

	    //$className = '';
		//if (!empty($task['Tags']))
		//	foreach ($task['Tags'] as $tag)
			//	$className .= ' '.$tag;

		$max = min($this->days, $endIdx + ($on ? 2 : 1));
	    for ($i; $i < $max; $i++)
	    {
	    	$class = $this->getClassName($weekDay, $i).($on ? '' : ' finished'.($i == 0 && $i == $endIdx ? ' ended' : ''));

	        if ($on && $i < $startIdx)
	        	$html .= '<td class="btnStartline'.$class.'" id="SetStart&i='.$taskId.'"><div class="dragger draggerSmall" onmousedown="timeline.SetDateline(event, \''.date('Y/m/d', $task['Start']).'\'); return false"><div class="draggingicon startlineicon cat'.$task['ListCategory'].'"></div></div></td>';
	        else if ($on && $i > $endIdx)
	        	$html .= '<td class="btnDeadline'.$class.'" id="SetDeadline&i='.$taskId.'"><div class="draggingicon deadlineicon cat'.$task['ListCategory'].'"></div><div class="dragger draggerSmall" onmousedown="timeline.SetDateline(event, \''.date('Y/m/d', $task['Deadline']).'\'); return false"></div></td>';
	        else if ($i >= $startIdx && $i <= $endIdx)
	        {
				$span = min($endIdx - $i + 1, $this->days - $i);
	            $html .= '<td colspan="'.$span.'" class="filled cat'.$task['ListCategory'].$class.'">';
				$html .= $this->getTitle($task['Title'], 2 * $this->cellWidth);
				$html .= $on ? '<div class="dragger" onmousedown="timeline.MoveTimeline(event, \''.$taskId.'\'); return false"></div>' : '';
				$html .= '</td>';
	            $i += $span - 1;
	        }
	    }

		return $html;
	}


	private function getTitle($title, $maxWidth)
	{
		$title = strip_tags($title);
        $title = explode('#', $title);
    	return '<div class="title" style="max-width: '.$maxWidth.'px"><span>'.$title[0].'</span></div>';
	}


	private function createGrid(&$i, $endIdx, $weekDay)
	{
    	$html = '';
    	for ($i; $i < $endIdx; $i++)
        	$html .= '<td class="'.$this->getClassName($weekDay, $i).'"></td>';

		return $html;
	}
}
?>

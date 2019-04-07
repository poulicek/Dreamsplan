var timeline = new timeline();

function timeline()
{
	this.SetDateline = function(e, selectedDate)
	{
		e = core.CatchEvent(e);

		var cellNode = core.GetParentNode(core.GetEl(e), 'TD');
		var cellX = e.clientX ? e.clientX : e.x;
		var cellWidth = cellNode.clientWidth;
		var method = cellNode.id;
   		var lastShift = null;

		document.onmouseup = function(e)
		{
   			document.onmousemove = null;
			document.onmouseup = null;

			if (lastShift == null)
				tasklists.SelectDeadline(cellNode, null, selectedDate);
		}

		document.onmousemove = function(e)
	  	{
        	if (tasklists.Loading)
	  	    	return;

        	e = window.event ? window.event : e;
	  		var x = e.clientX ? e.clientX : e.x;

        	selectedDate = new Date(selectedDate);
			var shift = parseInt((x - cellX) / cellWidth);
			if (shift != lastShift)
			{
				lastShift = shift;
            	core.CallApi(method + '&v=' + parseInt(selectedDate.getTime() / 1000 - selectedDate.getTimezoneOffset() * 60 + shift * 86400), tasklists.LoadTaskListsCallback);
			}
	  	};
	}
	
	
	this.MoveTimeline = function(e, taskId)
	{
    	e = core.CatchEvent(e);

		var cellNode = core.GetEl(e);
		if (cellNode.tagName != 'TH')
			cellNode = core.GetParentNode(cellNode, 'TD');

		var cellX = e.clientX ? e.clientX : e.x;
		var cellWidth = cellNode.clientWidth / cellNode.colSpan;

	    document.onmousemove = function(e)
	  	{
	  	    if (tasklists.Loading)
	  	        return;
	  	        
	  		var x = e.clientX ? e.clientX : e.x;
			var shift = parseInt((cellX - x) / cellWidth);
			if (shift)
			{
   				if (taskId)
   				{
   					core.CallApi('ShiftTask&i=' + taskId + '&v=' + (-shift * 86400), function(html)
					{
					   tasklists.LoadTaskListsCallback(html);
					});
   				}
   				else
					tasklists.ShiftDate(shift);
				cellX = x;
			}
	  	};
	  	
		document.onmouseup = function(e)
		{
   			document.onmousemove = null;
			document.onmouseup = null;
		}
	}

}
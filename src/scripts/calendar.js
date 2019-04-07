var calendar = new calendar();

function calendar()
{
	this.FirstDayIndex = 1;
	this.Date = null;
	this.Element = null;
	this.SelectedDate = null;
	this.ParentElement = null;

	this.OnSelected = null;
	this.OnShown = null;
	this.OnClosed = null;

	this.Show = function(parent, selectedDate, showDate)
	{
		showDate = showDate ? new Date(showDate) : new Date();
		
	    this.Close();
	    this.SelectedDate = selectedDate ? new Date(selectedDate) : null;
	    this.Date = new Date((this.SelectedDate ? this.SelectedDate : showDate).toDateString());
	    this.Date.setDate(1);
	    
	    var el = this.Create();
	    var loc = getLocation(parent);
	    var windowWidth = document.documentElement.clientWidth === 0 ? document.body.offsetWidth : document.documentElement.clientWidth;
	    
		document.body.appendChild(el);

	    this.Element = el;
	    this.ParentElement = parent;

	    if (this.OnShown)
	        this.OnShown();

		el.style.display = '';
	    el.style.left = Math.min(loc[0], windowWidth - el.clientWidth - 16) + 'px';
		el.style.top = loc[1] + 'px';
		this.ScrollIntoView();
	}

	this.Close = function()
	{
		if (this.Element != null)
		{
		    this.Element.parentNode.removeChild(this.Element);
		    
		    if (this.OnClosed)
	        	this.OnClosed();
	        	
			this.Date = null;
			this.Element = null;
			this.SelectedDate = null;
			this.ParentElement = null;
		}
	}
	
	this.ScrollIntoView = function()
	{
		if (!this.Element)
			return;
		core.ScrollIntoView(this.Element);
	}

	this.NextMonth = function()
	{
	    if (this.Element)
	    {
	        this.Date.setMonth(this.Date.getMonth() + 1);
	    	this.Element.replaceChild(this.Create().firstChild, this.Element.firstChild);
	    }
	}
	
	this.PrevMonth = function()
	{
		if (this.Element)
	    {
	        this.Date.setMonth(this.Date.getMonth() - 1);
	    	this.Element.replaceChild(this.Create().firstChild, this.Element.firstChild);
	    }
	}

	this.SelectDate = function(date)
	{
	    this.SelectedDate = new Date(date);
	    this.Date = new Date(this.SelectedDate.toDateString());
	    this.Date.setDate(1);
	    
	    if (this.OnSelected)
			if (this.OnSelected() === false)
				this.SelectedDate = null;
	    this.Element.replaceChild(this.Create().firstChild, this.Element.firstChild);
	}
	
	this.Create = function()
 	{
	    var el = document.createElement('DIV');
	    el.className = 'calendar';
	    el.style.display = 'none';
	    
	    var tb = document.createElement('TABLE');
		el.appendChild(tb);
		
		// heading
		var tr = document.createElement('TR');
		tb.appendChild(tr);
		
		// navigation header
		var th = document.createElement('TH');
		tr.appendChild(th);
		th.colSpan = 7;
		th.className = 'navigation';
		th.innerHTML = '<button onclick="calendar.PrevMonth()" onfocus="this.blur()" class="backward">◄</button><button onclick="calendar.NextMonth()" onfocus="this.blur()" class="forward">►</button>'+ this.Date.toDateString().replace(/([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+([^\s]+)/, '$2 $4');

		// weekdays header
		tr = document.createElement('TR');
		tb.appendChild(tr);
		var days = new Array('Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa');
		for (var i = 0; i < 7; i++)
		{
			th = document.createElement('TH');
			tr.appendChild(th);
			th.innerHTML = days[(this.FirstDayIndex + i) % 7];
		}

		// days
		var today = new Date().toDateString();
		var firstDayValue = !this.Date.getDay() && this.FirstDayIndex ? 7 : this.Date.getDay();
		var tmpDate = new Date(this.Date.getFullYear(), this.Date.getMonth(), this.FirstDayIndex - firstDayValue + 1);

		for (var i = 0; i < 6; i++)
		{
			tr = document.createElement('TR');
			tb.appendChild(tr);
			
			for (var j = 0; j < 7; j++)
			{
				var td = document.createElement('TD');
				tr.appendChild(td);

				td.className += tmpDate.toDateString() == today ? ' today' : '';
				td.className += tmpDate.getMonth() != this.Date.getMonth() ? ' otherMonth' : '';
				td.className += this.SelectedDate && tmpDate.toDateString() == this.SelectedDate.toDateString() ? ' selected' : '';
				
				td.innerHTML = '<a href="javascript:calendar.SelectDate(\'' + tmpDate.toDateString() + '\')" onfocus="this.blur()">' + tmpDate.getDate() + '</a>';
				tmpDate.setDate(tmpDate.getDate() + 1);
			}
		}
		return el;
	}
	
	function getLocation(el)
	{
        var curleft = curtop = 0;
		if (el.offsetParent)
		{
   			do {
				curleft += el.offsetLeft;
				curtop += el.offsetTop;
			} while (el = el.offsetParent);
		}
		else if(el.x)
   			return [el.x, el.y];
		return [curleft, curtop];
    };
}
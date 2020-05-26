class Console extends PanelClass {
	initialise( data )	{
		super.initialise(data);
		this.div.style.backgroundColor = "#C0C0C0";
		this.workarea.style.backgroundColor = "#CFCFCF";
		this.workspace.registerConsole(this);
		this.title.innerHTML = "Console";
	}
	
	displayMessage (message)	{
		this.div.innerHTML += message;
	}
	
}


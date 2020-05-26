class PanelClass {
	constructor( workspace, div, paneName )	{
		this.div = div;
		this.workarea = null;
		this.header = null;
		this.title = null;
		this.paneName = paneName;
		this.workspace = workspace;
		this.noTitle = false;
		
		this.workspace.getUser().fetchUserData( paneName, this.initialise.bind(this) );
	}

	initialise( data, displayTitle = true )	{
		if ( displayTitle )	{
			this.header = document.createElement('div');
			this.title = document.createElement('span');
			this.title.innerHTML= this.paneName;
			this.title.style.height = "10%";
			this.header.appendChild(this.title);
			this.workarea = document.createElement('div');
			this.workarea.style.height = "90%";
			this.div.appendChild(this.header);
			this.div.appendChild(this.workarea);
		}
	}
}
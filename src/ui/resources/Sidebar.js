class Sidebar extends PanelClass {
	constructor( workspace, div, paneName )	{
		super(workspace, div, paneName);
		this.menu = null;
	}

	initialise( data )	{
		super.initialise(data);
		this.div.style.backgroundColor = "#B0B0B0";
		this.workarea.style.overflow = "auto";
		this.workarea.style['white-space'] = "nowrap";
		
		this.workspace.getUser().fetchUserData ( "Project", function(menuStructure) {
			this.title.innerHTML = "Projects";
			this.menu = new Tree( "SideBar", menuStructure);
			this.menu.setClickCallback (this.clickClicked.bind(this));
			this.menu.setAddCallback (this.addClicked.bind(this));
			this.menu.setDeleteCallback (this.deleteClicked.bind(this));
			this.menu.setUpdateCallback (this.menuUpdated.bind(this));
			this.menu.display(this.workarea);
		}.bind(this) );
	}

	clickClicked( label, data )	{
		this.workspace.select(label, data.classToUse);
	}
	
	addClicked( label, data )	{
		console.log("Add " + data.title);
		// Create new item, need to generate name		
//		console.log(data.nodes);
		
		// TODO open tab for new item
	}
	
	deleteClicked( data )	{
		console.log("Delete " + data.title);
	}
	
	menuUpdated ( data )	{
		this.workspace.getUser().setUserData("Project", data);
	}
}


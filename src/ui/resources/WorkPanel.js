class WorkPanel extends PanelClass {
	constructor( workspace, div, paneName )	{
		super(workspace, div, paneName);
	}

	initialise( data )	{
		super.initialise(data, false);
		
		this.workspace.getUser().
			fetchUserData( this.paneName, function(tabs)	{
					this.tabs = new Tabs( this.div, this.workspace, tabs, this.paneName );
					this.tabs.display();
				}.bind(this)
			);
		
		// TODO this will have to vary when a user focuses on panel
		this.workspace.setCurrentWorkPanel(this);
	}

	add ( tabName, tabClass )	{
		if ( this.tabs.tabExists( tabName ) )	{
			this.tabs.clickTab( tabName );
		}
		else	{
			this.workspace.getUser().
				fetchUserData( "tab_"+tabClass, function(tab)	{
					this.newTab(tabName, tab);
				}.bind(this.tabs)
		);
			
		}
		
	}
}


class Tabs	{
	constructor( parent, workspace, data, paneName )	{
		this.parent = parent;
		this.workspace = workspace;
		this.data = data;
		this.paneName = paneName;
	}
	
	display ()	{
		this.header = document.createElement('div');
		this.header.className = "tabs";
		this.header.style.height = "7%";
		this.header.style.width = "100%";
		this.workarea = document.createElement('div');
		this.workarea.style.height = "93%";
		this.workarea.style.width = "100%";
		this.workarea.style.overflow = "auto";
		this.workarea.style['white-space'] = "nowrap";
		this.parent.appendChild(this.header);
		this.parent.appendChild(this.workarea);
		
		if ( this.data != null && this.data.tabs != null ){
			for ( const tab in this.data.tabs )	{
				this.addTab ( tab );
			}
		}
	}
	
	newTab ( tabName, tab )	{
		this.data.tabs[tabName] = tab;
		this.addTab( tabName );
		this.clickTab( tabName );
	}
	
	addTab ( tab )	{
		this.data.tabs[tab].tag = document.createElement('button');
		this.data.tabs[tab].tag.appendChild(
				document.createTextNode(this.data.tabs[tab].title));
		this.data.tabs[tab].closeTab = document.createElement('span');
		this.data.tabs[tab].closeTab.className = "closetab glyphicon glyphicon-remove";
		this.data.tabs[tab].tag.appendChild(this.data.tabs[tab].closeTab);
		this.data.tabs[tab].closeTab.onclick = function(event)	{
			this.closeTab(tab);
			event.stopPropagation();
		}.bind(this);
		
		this.data.tabs[tab].div = document.createElement('div');
		this.workarea.appendChild(this.data.tabs[tab].div);
		if ( this.data.activeTab == tab)	{
			this.data.tabs[tab].tag.classList.add("active");
		}
		this.header.appendChild(this.data.tabs[tab].tag);
		// Associate click
		this.data.tabs[tab].tag.onclick = function() {
			this.clickTab(tab);
		}.bind(this);
		// load class for tab into workarea
		this.data.tabs[tab].handler = 
			new Tabs.classes[this.data.tabs[tab].classToUse]
					(this.data.tabs[tab].div, this.workspace, tab);
		if ( this.data.activeTab == tab)	{
			this.data.tabs[tab].div.style.display = "block";
		}
		else	{
			this.data.tabs[tab].div.style.display = "none";
		}
		// fetch data for the tab
		this.workspace.getUser().fetchUserData( 
				tab+"_details", this.data.tabs[tab].handler.display.
					bind(this.data.tabs[tab].handler) );
	}
	
	tabExists ( tabName )	{
		return this.data.tabs[tabName] != null;
	}
	
	closeTab ( tab )	{
		let status = this.data.tabs[tab].div.style.display;
		this.removeElement(this.data.tabs[tab].tag);
		this.removeElement(this.data.tabs[tab].div);
		this.removeElement(this.data.tabs[tab].closeTab);
		delete this.data.tabs[tab];
		this.workspace.getUser().setUserData( this.paneName+"_details", this.data );
		if ( status == "block" && Object.keys(this.data.tabs).length > 0 )	{
			let newTab = Object.keys(this.data.tabs)[0];
			// Activate first tab
			this.data.tabs[newTab].div.style.display = "block";
		}
	}
	
	removeElement ( e )	{
		e.parentNode.removeChild(e);
	}
	
	clickTab ( tabSelected )	{
		// Loop through tabs reseting active, Set this one active
		for ( const tab in this.data.tabs )	{
			if ( tabSelected == tab)	{
				this.data.tabs[tab].tag.classList.add("active");
				this.data.activeTab = tabSelected;
				this.data.tabs[tab].div.style.display = "block";
			}
			else	{
				this.data.tabs[tab].tag.classList.remove("active");
				this.data.tabs[tab].div.style.display = "none";
			}
			// TODO ? save config
		}
	}
	
	static classes = [];
	static registerClass ( className, classToRegister )	{
		Tabs.classes[className] = classToRegister;
	}
}
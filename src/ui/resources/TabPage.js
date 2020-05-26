class TabPage	{
	constructor( div, workspace, label )	{
		this.div = div;
		this.workspace = workspace;
		this.label = label;
		this.data = null;
		
		// Load template
		this.div.innerHTML = this.constructor.getTemplate();
	}

	display(data)	{
		//this.div.innerHTML = "Tab not configured!";
	}

	saveTab ()	{
		this.workspace.getUser().setUserData( this.label+"_details", this.data );
	}
	
	// TODO Some tabs will need to customize the HTML each time
	static getTemplate () {
		return '';
	}
	
	displayDataInTab ()	{
		for ( const fieldName in this.data )	{
			let field = this.div.querySelector('[data-id="'+fieldName+'"]');
			if (field != null )	{
				field.value = this.data[fieldName];
				field.onblur = function(fieldName, field, event)	{
					this.data[fieldName] = field.value;
				}.bind(this, fieldName, field);
			}
		}
	}
	
}
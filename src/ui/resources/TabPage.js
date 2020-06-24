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
			let field = this.getByDataID(this.div,fieldName);
			if (field != null )	{
				// If a table, need to deal with that
				if ( field.tagName == "TABLE" )	{
					this.displayTable(field, this.data[fieldName]);
				}
				field.value = this.data[fieldName];
				field.onblur = function(fieldName, field)	{
					this.data[fieldName] = field.value;
				}.bind(this, fieldName, field);
			}
		}
	}
	
	displayTable ( table, data )	{
		// Find template row for table
		const defTemplate = this.getByDataID(table, "defTemplate");
		for ( let i = 0; i < data.length; i++ )	{
			let newRow = defTemplate.cloneNode(true);
			newRow.style.display = "";
			newRow.setAttribute('data-id', newRow.getAttribute('data-id') + i );
			table.appendChild(newRow);
			for (const field in data[i] )	{
				let colField = this.getByDataID(newRow,field);
				colField.value = data[i][field];
				colField.onblur = function(data,  i, field, colField)	{
					data[i][field] = colField.value;
				}.bind(this, data, i, field, colField);
			}
		}
	}
	
	getByDataID ( base, name )	{
		return base.querySelector("[data-id='" + name + "']");
	}
}
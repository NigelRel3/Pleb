class ProjectConfig extends TabPage {
	display(data)	{
		this.data = data;
		this.displayDataInTab();
		document.getElementById("saveProjectConfig").onclick = this.saveTab.bind(this)
			
	}
	
	saveTab (event)	{
		event.preventDefault();
		super.saveTab();
	}
	
	static getTemplate () {
		return `
		<div class="tabcontainer">
		<form>
			<label for="pctprojectName">Project Name</label>
	    	<input type="text" placeholder="Enter Project Name" id="pctprojectName" 
	    			data-id="ProjectName" required>
			<br />
	    	<label for="pctdescription">Description</label>
	    	<textarea id="pctdescription" rows="4" cols="40" 
	    			data-id="Description"></textarea>
			<br />
	    	<label for="pctdefaultdateformat">Default date format</label>
	    	<select id="pctdefaultdateformat" data-id="DefaultDateFormat">
	    		<option value="dd/mm/YYYY">dd/mm/yyyy</option>
	    		<option value="mm/dd/YYYY">mm/dd/yyyy</option>
	    		<option value="mm-dd-YYYY">dd-mm-yyyy</option>
	    	</select>
			<br />
			<br />
	    	<button id="saveProjectConfig" type="submit">Save</button>
	    </form>
	    </div>`;
	}
}
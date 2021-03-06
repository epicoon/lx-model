class ActionReport #lx:namespace lx.models {
	constructor(serviceName) {
		this.service = serviceName;
		this.actions = [];
	}

	addAction(title, data) {
		this.actions.push({title, data});
	}
}

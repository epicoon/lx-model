#lx:namespace lx.models;
class ActionReport {
	constructor(serviceName) {
		this.service = serviceName;
		this.actions = [];
	}

	addAction(title, data) {
		this.actions.push({title, data});
	}
}

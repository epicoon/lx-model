#lx:namespace lx.models;
class ServiceCategoryData extends lx.BindableModel {
	#lx:schema
		count;

	constructor(data) {
		super();
		this.serviceCategory = data.serviceCategory;
		this.servicesNeedUpdate = [];
	}

	getTitle() {
		return this.serviceCategory;
	}

	hasStatus() {
		return false;
	}

	addServiceNeedUpdate(serviceData) {
		var title = serviceData.getTitle();
		if (this.servicesNeedUpdate.includes(title)) return;
		this.servicesNeedUpdate.push(title);
		this.count = this.servicesNeedUpdate.len;
	}

	removeServiceNeedUpdate(serviceData) {
		var title = serviceData.getTitle();
		if (!this.servicesNeedUpdate.includes(title)) return;
		this.servicesNeedUpdate.lxRemove(title);
		this.count = this.servicesNeedUpdate.len;
	}
}

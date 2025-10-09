
console.log('Mandatory.js loaded');

async function markAsRead(event){
    var target = event.target;
	if(target.dataset.postId != undefined){
		Main.showLoader(target);
		
		var formData = new FormData();
		formData.append('user-id', target.dataset.userId);
		formData.append('post-id', target.dataset.postId);

        var response    = await FormSubmit.fetchRestApi('mandatory_content/mark_as_read', formData);
		
        if(response){
            Main.displayMessage(response, 'success', false);
            document.querySelectorAll('.mandatory-content-button, .mandatory-content-warning').forEach(el=>el.remove());
        }
	}
}

async function markAllAsRead(event){
    var target  = event.target;
    var loader  = Main.showLoader(target);
    
    var formData = new FormData();
    formData.append('user-id', target.dataset.userId);

    var response    = await FormSubmit.fetchRestApi('mandatory_content/mark_all_as_read', formData);
    
    if(response){
        Main.displayMessage(response, 'success', false);
        document.querySelectorAll('.mark-all-as-read').forEach(el=>el.remove());
    }

    loader.remove();
}

document.addEventListener("DOMContentLoaded",function() {
    document.querySelectorAll('.mark-as-read').forEach(el=>el.addEventListener('click', markAsRead));

    document.querySelectorAll('.mark-all-as-read').forEach(el=>el.addEventListener('click', markAllAsRead));
})
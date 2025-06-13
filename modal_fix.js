// Script to fix modal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get the modal
    const modal = document.getElementById('notificationModal');
    
    // Get the close button
    const closeBtn = document.getElementById('closeModalBtn');
    
    // When the user clicks on the close button, close the modal
    if (closeBtn) {
        closeBtn.onclick = function() {
            modal.style.display = "none";
        }
    }
    
    // When the user clicks anywhere outside of the modal content, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
    
    // Add this script to the page
    console.log("Modal fix script loaded");
});
/* This script handles the submission of forms.

    *   File name: form-submit.js
    *   Created by: Brandon Hills
    *   Contact: brandon@goodstewarddesigns.com
    *   Last modified: 4/19/25
 */

document.addEventListener('submit', (submit) => {

    //  Store references to form to make later code easier to read :)
    const form = submit.target;
    const statusBusy = form.querySelector('.status-busy');
    const statusFailure = form.querySelector('.status-failure');

    //  Remember the last active field for focus fallback:
    const lastActive = document.activeElement;

    //  Show busy status and disable form:
    statusBusy.hidden = false;
    statusBusy.tabIndex = -1;
    statusBusy.focus();
    Array.from(form.elements).forEach((field) => (field.disabled = true));  //  Disable all form elements while fetching:
    statusFailure.hidden = true;                                            //  Keep error message hidden:

    //  Gather form data:
    const formData = new FormData(form);

    fetch(form.action, {
        method: form.method,
        body: form.Data
    })

    .then((res) => {
        if (!res.ok) throw new Error('Server responded with an error.');
        return res.json();
    })

    .then((data) => {
        const responseDiv = document.createElement('div');
        responseDiv.classList.add('response');

        const responseP = document.createElement('p');
        responseP.setAttribute('role', 'alert');
        responseP.tabIndex = -1;
        responseP.innerHTML = data.message || 'Thank you.';

        responseDiv.append(responseP);

        //  Replace form with response:
        form.parentNode.replaceChild(responseDiv, form);

        //  Update styling contain if needed:
        responseDiv.parentNode.classList.add('responded');
        responseDiv.parentNode.removeAttribute('style');

        //  Move focus to message:
        responseP.focus();
    })

    .catch((err) => {
        console.error(err);

        Array.from(form.elements).forEach((field) => (field.disabled = false));
        lastActive?.focus();

        statusBusy.hidden = true;
        statusFailure.hidden = false;
        statusFailure.textContent = 'There was a problem submitting your message. Please try again.';
    });

    //  Prevent default form behavior:
    submit.preventDefault();

});
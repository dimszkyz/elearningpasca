// This file is part of Moodle - http://moodle.org/.

/**
 * Move the Pasca sync button beside the core Add a new user action.
 *
 * @module     local_pascasync/sync_button
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialise the report action button.
 *
 * @param {string} buttonId
 */
export const init = (buttonId) => {
    const placeButton = () => {
        const button = document.getElementById(buttonId);
        if (!button) {
            return;
        }

        const addUserButton = document.querySelector('[data-action="add-user"]');
        if (addUserButton?.parentNode) {
            button.classList.add('me-2');
            addUserButton.parentNode.insertBefore(button, addUserButton);
        } else {
            const wrapper = document.querySelector('[data-region="report-user-list-wrapper"]');
            wrapper?.prepend(button);
            button.classList.add('mb-3');
        }

        button.hidden = false;
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', placeButton, {once: true});
    } else {
        placeButton();
    }
};

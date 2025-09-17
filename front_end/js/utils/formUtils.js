// Module : formUtils.js
// Helpers pour gestion dynamique des champs de formulaire

export function addDateField(formRowSelector) {
    const formRow = document.querySelector(formRowSelector);
    if (!formRow) return;
    const dateGroup = document.createElement('div');
    dateGroup.className = 'form-group';
    const dateInput = document.createElement('input');
    dateInput.type = 'date';
    dateInput.id = 'travel-date';
    dateInput.style.cssText = [
        'width: 100%',
        'padding: 1rem',
        'border: none',
        'border-radius: 8px',
        'background-color: white',
        'font-size: 1rem',
        'color: #333'
    ].join('; ');
    dateGroup.appendChild(dateInput);
    formRow.appendChild(dateGroup);
}

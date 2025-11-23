export type ProcessType = 'calendar' | 'display' | 'contact';

export class Rule {
    public name: string;
    public processes: ProcessType[];

    private row: HTMLTableRowElement;

    constructor(parentBody: HTMLTableSectionElement, index: number) {
        this.name = '';
        this.processes = [];

        this.row = document.createElement('tr');
        this.row.innerHTML = `
            <td><input type="text" placeholder="Nom de propriété"></td>
            <td>
                <select multiple>
                    <option value="calendar">Calendar</option>
                    <option value="display">Display</option>
                    <option value="contact">Contact</option>
                </select>
            </td>
            <td><button type="button" class="button danger crm-remove-rule">Supprimer</button></td>
        `;

        parentBody.appendChild(this.row);

        this.row.querySelector('.crm-remove-rule')?.addEventListener('click', () => {
            this.row.remove();
        });
    }
}

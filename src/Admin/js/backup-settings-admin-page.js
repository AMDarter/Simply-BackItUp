document.addEventListener('DOMContentLoaded', function() {
    const BackItUpSettings = {
        init: function() {
            this.createContainer();
            this.createHeading();
            this.createFrequencySetting();
            this.createTimeSetting();
            this.createEmailSetting();
            this.createButtons();
            this.appendContainer();
            this.addEventListeners();
        },

        createContainer: function() {
            this.container = document.createElement('div');
            this.container.className = 'wrap';
        },

        createHeading: function() {
            const heading = document.createElement('h1');
            heading.textContent = 'Backup Settings';
            this.container.appendChild(heading);
        },

        createFrequencySetting: function() {
            const frequencyLabel = document.createElement('label');
            frequencyLabel.setAttribute('for', 'backup-frequency');
            frequencyLabel.textContent = 'Backup Frequency';
            const frequencySelect = document.createElement('select');
            frequencySelect.id = 'backup-frequency';
            frequencySelect.innerHTML = `
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
            `;
            const frequencyContainer = document.createElement('p');
            frequencyContainer.appendChild(frequencyLabel);
            frequencyContainer.appendChild(frequencySelect);
            this.container.appendChild(frequencyContainer);
        },

        createTimeSetting: function() {
            const timeLabel = document.createElement('label');
            timeLabel.setAttribute('for', 'backup-time');
            timeLabel.textContent = 'Backup Time';
            const timeInput = document.createElement('input');
            timeInput.type = 'time';
            timeInput.id = 'backup-time';
            timeInput.value = '03:00';
            const timeContainer = document.createElement('p');
            timeContainer.appendChild(timeLabel);
            timeContainer.appendChild(timeInput);
            this.container.appendChild(timeContainer);
        },

        createEmailSetting: function() {
            const emailLabel = document.createElement('label');
            emailLabel.setAttribute('for', 'backup-email');
            emailLabel.textContent = 'Backup Email';
            const emailInput = document.createElement('input');
            emailInput.type = 'email';
            emailInput.id = 'backup-email';
            emailInput.placeholder = 'Enter email address';
            const emailContainer = document.createElement('p');
            emailContainer.appendChild(emailLabel);
            emailContainer.appendChild(emailInput);
            this.container.appendChild(emailContainer);
        },

        createButtons: function() {
            const saveButton = document.createElement('button');
            saveButton.id = 'save-settings';
            saveButton.className = 'button button-primary';
            saveButton.textContent = 'Save Settings';

            const backupNowButton = document.createElement('button');
            backupNowButton.id = 'backup-site';
            backupNowButton.className = 'button button-primary';
            backupNowButton.textContent = 'Backup Now';

            this.container.appendChild(saveButton);
            this.container.appendChild(backupNowButton);
        },

        appendContainer: function() {
            document.getElementById('simply-backitup-settings').appendChild(this.container);
        },

        addEventListeners: function() {
            document.getElementById('backup-site').addEventListener('click', function() {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', SimplyBackItUp.ajaxurl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        alert('Backup completed successfully.');
                        location.reload();
                    } else {
                        alert('Backup failed.');
                    }
                };
                xhr.send('action=amdarter_backup_site&nonce=' + encodeURIComponent(SimplyBackItUp.nonce));
            });
        }
    };

    if (document.getElementById('simply-backitup-settings')) {
        BackItUpSettings.init();
    }
});

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
            this.populateSettings();
            this.addEventListeners();
        },

        createContainer: function() {
            this.container = document.createElement('div');
            this.container.className = 'wrap';
        },

        createAlert: function(message, type = 'success') {
            const alert = document.createElement('div');
            alert.id = 'SimplyBackItUpAlert';
            alert.className = `notice notice-${type}`;
            alert.innerHTML = `<p>${message}</p>`;
            this.container.prepend(alert);
        },

        removeAlert: function() {
            const alert = document.getElementById('SimplyBackItUpAlert');
            if (alert) {
                alert.remove();
            }
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

        populateSettings: function() {
            document.getElementById('backup-frequency').value = SimplyBackItUp.settings.frequency;
            document.getElementById('backup-time').value = SimplyBackItUp.settings.time;
            document.getElementById('backup-email').value = SimplyBackItUp.settings.email;
        },

        createBackupNowProgress: function() {
            const progressContainer = document.createElement('div');
            progressContainer.id = 'backup-now-progress-container';

            const progress = document.createElement('div');
            progress.id = 'backup-now-progress';
            progress.className = 'progress';

            const progressBar = document.createElement('div');
            progressBar.className = 'progress-bar';
            progressBar.setAttribute('role', 'progressbar');
            progressBar.setAttribute('aria-valuemin', '0');
            progressBar.setAttribute('aria-valuemax', '100');
            progressBar.setAttribute('aria-valuenow', '0');
            progressBar.style.width = '0%';

            const progressText = document.createElement('div');
            progressText.id = 'backup-now-progress-text';
            progressText.className = 'progress-bar-text';

            const progressList = document.createElement('ul');
            progressList.id = 'backup-now-progress-list';

            progress.appendChild(progressBar);
            progressContainer.appendChild(progress);
            progressContainer.appendChild(progressText);
            progressContainer.appendChild(progressList);
            this.container.prepend(progressContainer);
        },

        updateBackupNowProgress: function(value, message) {
            const progressBar = document.querySelector('#backup-now-progress .progress-bar');
            progressBar.style.width = `${value}%`;
            progressBar.setAttribute('aria-valuenow', value);
            document.getElementById('backup-now-progress-text').textContent = `${value}%`;

            const progressList = document.getElementById('backup-now-progress-list');
            const listItem = document.createElement('li');
            listItem.textContent = message;
            progressList.appendChild(listItem);
        },

        removeBackupNowProgress: function() {
            const progress = document.getElementById('backup-now-progress-container');
            if (progress) {
                progress.remove();
            }
        },

        performStep1: function() {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', SimplyBackItUp.ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            BackItUpSettings.updateBackupNowProgress(0, 'Starting backup...');
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        BackItUpSettings.updateBackupNowProgress(response.data.progress, response.data.message);
                        BackItUpSettings.performStep2();
                    } else {
                        BackItUpSettings.updateBackupNowProgress(0, response.data.message);
                        BackItUpSettings.removeBackupNowProgress();
                    }
                } else {
                    BackItUpSettings.updateBackupNowProgress(0, 'Failed to start backup.');
                    BackItUpSettings.removeBackupNowProgress();
                }
            };
            const data = new URLSearchParams();
            data.append('action', 'simply_backitup_step1');
            data.append('nonce', SimplyBackItUp.nonce);
            xhr.send(data);
        },

        performStep2: function() {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', SimplyBackItUp.ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        BackItUpSettings.updateBackupNowProgress(response.data.progress, response.data.message);
                        BackItUpSettings.performStep3();
                    } else {
                        BackItUpSettings.updateBackupNowProgress(33, response.data.message);
                        BackItUpSettings.removeBackupNowProgress();
                    }
                } else {
                    BackItUpSettings.updateBackupNowProgress(33, 'Failed to export database.');
                    BackItUpSettings.removeBackupNowProgress();
                }
            };
            const data = new URLSearchParams();
            data.append('action', 'simply_backitup_step2');
            data.append('nonce', SimplyBackItUp.nonce);
            xhr.send(data);
        },

        performStep3: function() {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', SimplyBackItUp.ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        BackItUpSettings.updateBackupNowProgress(response.data.progress, response.data.message);
                        setTimeout(() => {
                            BackItUpSettings.removeBackupNowProgress();
                        }, 5000);
                    } else {
                        BackItUpSettings.updateBackupNowProgress(66, response.data.message);
                        BackItUpSettings.removeBackupNowProgress();
                    }
                } else {
                    BackItUpSettings.updateBackupNowProgress(66, 'Failed to upload to cloud.');
                    BackItUpSettings.removeBackupNowProgress();
                }
            };
            const data = new URLSearchParams();
            data.append('action', 'simply_backitup_step3');
            data.append('nonce', SimplyBackItUp.nonce);
            xhr.send(data);
        },

        addEventListeners: function() {
            document.getElementById('backup-site').addEventListener('click', function() {
                BackItUpSettings.removeBackupNowProgress();
                BackItUpSettings.createBackupNowProgress();
                BackItUpSettings.updateBackupNowProgress(0);
                BackItUpSettings.performStep1();
            });

            document.getElementById('save-settings').addEventListener('click', function() {
                const formData = new FormData();
                formData.append('action', 'simply_backitup_save_settings');
                formData.append('nonce', SimplyBackItUp.nonce);
                formData.append('frequency', document.getElementById('backup-frequency').value);
                formData.append('time', document.getElementById('backup-time').value);
                formData.append('email', document.getElementById('backup-email').value);
                var xhr = new XMLHttpRequest();
                xhr.open('POST', SimplyBackItUp.ajaxurl, true);
                xhr.onload = function() {
                    BackItUpSettings.removeAlert();
                    if (xhr.status >= 200 && xhr.status < 300) {
                        BackItUpSettings.createAlert('Settings saved.');
                    } else {
                        BackItUpSettings.createAlert('Failed to save settings.', 'error');
                    }
                    setTimeout(() => {
                        BackItUpSettings.removeAlert();
                    }, 5000);
                };
                xhr.send(formData);
            });
        }
    };

    if (document.getElementById('simply-backitup-settings')) {
        BackItUpSettings.init();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const SimplyBackItUpSettingsForm = {

        Settings: SimplyBackItUp?.settings || {},

        init: function () {
            console.log(this.Settings);
            this.createContainer();
            this.createHeading(1, 'Simply BackItUp');
            this.createHeading(2, 'Backup Settings');
            this.createFrequencySetting();
            this.createTimeSetting();
            this.createEmailSetting();
            this.createStorageLocationSetting();
            this.createStorageApiCredentialContainer();
            this.createLastBackUpTime();
            this.createButtons();
            this.appendContainer();
            this.addEventListeners();
        },

        createContainer: function () {
            this.container = document.createElement('div');
            this.container.className = 'wrap';
        },

        createAlert: function (message, type = 'success') {
            const alert = document.createElement('div');
            alert.id = 'SimplyBackItUpAlert';
            alert.className = `notice notice-${type}`;
            alert.innerHTML = `<p>${message}</p>`;
            this.container.prepend(alert);
        },

        removeAlert: function () {
            const alert = document.getElementById('SimplyBackItUpAlert');
            if (alert) {
                alert.remove();
            }
        },

        createHeading: function (level = 1, text = '') {
            const heading = document.createElement('h' + level);
            heading.textContent = text;
            this.container.appendChild(heading);
        },

        createFrequencySetting: function () {
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
            frequencySelect.childNodes.forEach(function (option) {
                if (option.value === SimplyBackItUp.settings.frequency) {
                    option.selected = true;
                }
            });
            const frequencyContainer = document.createElement('p');
            frequencyContainer.appendChild(frequencyLabel);
            frequencyContainer.appendChild(frequencySelect);
            this.container.appendChild(frequencyContainer);
        },

        createTimeSetting: function () {
            const timeLabel = document.createElement('label');
            timeLabel.setAttribute('for', 'backup-time');
            timeLabel.textContent = 'Backup Time';
            const timeInput = document.createElement('input');
            timeInput.type = 'time';
            timeInput.id = 'backup-time';
            timeInput.value = SimplyBackItUpSettingsForm.Settings.time || '03:00';
            const timeContainer = document.createElement('p');
            timeContainer.appendChild(timeLabel);
            timeContainer.appendChild(timeInput);
            this.container.appendChild(timeContainer);
        },

        createEmailSetting: function () {
            const emailLabel = document.createElement('label');
            emailLabel.setAttribute('for', 'backup-email');
            emailLabel.textContent = 'Backup Email';
            const emailInput = document.createElement('input');
            emailInput.value = SimplyBackItUpSettingsForm.Settings.email || '';
            emailInput.type = 'email';
            emailInput.id = 'backup-email';
            emailInput.placeholder = 'Enter email address';
            const emailContainer = document.createElement('p');
            emailContainer.appendChild(emailLabel);
            emailContainer.appendChild(emailInput);
            this.container.appendChild(emailContainer);
        },

        createStorageLocationSetting: function () {
            const storageLocationLabel = document.createElement('label');
            storageLocationLabel.setAttribute('for', 'backup-storage-location');
            storageLocationLabel.textContent = 'Storage Location';
            const storageLocationSelect = document.createElement('select');
            storageLocationSelect.id = 'backup-storage-location';
            storageLocationSelect.innerHTML = `
                <option value="Google Drive">Google Drive</option>
                <option value="Dropbox">Dropbox</option>
                <option value="OneDrive">OneDrive</option>
                <option value="Amazon S3">Amazon S3</option>
                <option value="FTP">FTP</option>
            `;
            storageLocationSelect.childNodes.forEach(function (option) {
                if (option.value === SimplyBackItUp.settings.backupStorageLocation) {
                    option.selected = true;
                }
            });
            storageLocationSelect.addEventListener('change', function () {
                SimplyBackItUpSettingsForm.Settings.backupStorageLocation = storageLocationSelect.value;
                SimplyBackItUpSettingsForm.updateStorageApiCredentialSetting();
            });
            const storageLocationContainer = document.createElement('p');
            storageLocationContainer.appendChild(storageLocationLabel);
            storageLocationContainer.appendChild(storageLocationSelect);
            this.container.appendChild(storageLocationContainer);
        },

        createStorageApiCredentialContainer: function () {
            this.storageApiCredentialContainer = document.createElement('div');
            this.storageApiCredentialContainer.id = 'storage-api-credential-container';
            this.createStorageApiCredentialSetting();
            this.container.appendChild(this.storageApiCredentialContainer);
        },

        updateStorageApiCredentialSetting: function () {
            this.storageApiCredentialContainer.innerHTML = '';
            this.createStorageApiCredentialSetting();
        },

        createStorageApiCredentialSetting: function () {
            switch (SimplyBackItUpSettingsForm.Settings.backupStorageLocation) {
                case 'Google Drive':
                    this.createGoogleDriveCredentialSetting();
                    break;
                case 'Dropbox':
                    this.createDropboxCredentialSetting();
                    break;
                case 'OneDrive':
                    this.createOneDriveCredentialSetting();
                    break;
                case 'Amazon S3':
                    this.createAmazonS3CredentialSetting();
                    break;
                case 'FTP':
                    this.createFtpCredentialSetting();
                    break;
                default:
                    break;
            }
        },

        createGoogleDriveCredentialSetting: function () {
            this.createCredentialInput('Google Drive API Key', 'google-drive-api-key', SimplyBackItUpSettingsForm.Settings?.backupStorageCredentials['google-drive-api-key'] || '');
            this.createCredentialInput('Google Drive Client ID', 'google-drive-client-id', SimplyBackItUpSettingsForm.Settings?.backupStorageCredentials['google-drive-client-id'] || '');
            this.createCredentialInput('Google Drive Client Secret', 'google-drive-client-secret', SimplyBackItUpSettingsForm.Settings?.backupStorageCredentials['google-drive-client-secret'] || '');
        },

        createDropboxCredentialSetting: function () {
            this.createCredentialInput('Dropbox Access Token', 'dropbox-access-token', SimplyBackItUpSettingsForm.Settings?.backupStorageCredentials['dropbox-access-token'] || '');
        },

        createOneDriveCredentialSetting: function () {
            this.createCredentialInput('OneDrive Client ID', 'one-drive-client-id', SimplyBackItUpSettingsForm.Settings?.backupStorageCredentials['one-drive-client-id'] || '');
            this.createCredentialInput('OneDrive Client Secret', 'one-drive-client-secret', SimplyBackItUpSettingsForm.Settings?.backupStorageCredentials['one-drive-client-secret'] || '');
        },

        createAmazonS3CredentialSetting: function () {
            this.createCredentialInput('Amazon S3 Access Key', 'amazon-s3-access-key', SimplyBackItUpSettingsForm.Settings?.backupStorageCredentials['amazon-s3-access-key'] || '');
            this.createCredentialInput('Amazon S3 Secret Key', 'amazon-s3-secret-key', SimplyBackItUpSettingsForm.Settings?.backupStorageCredentials['amazon-s3-secret-key'] || '');
            this.createCredentialInput('Amazon S3 Bucket Name', 'amazon-s3-bucket-name', SimplyBackItUpSettingsForm.Settings?.backupStorageCredentials['amazon-s3-bucket-name'] || '');
            this.createCredentialInput('Amazon S3 Region', 'amazon-s3-region', SimplyBackItUpSettingsForm.Settings?.backupStorageCredentials['amazon-s3-region'] || '');
        },

        createFtpCredentialSetting: function () {
            this.createCredentialInput('FTP Host', 'ftp-host', SimplyBackItUpSettingsForm.Settings?.backupStorageCredentials['ftp-host'] || '');
            this.createCredentialInput('FTP Username', 'ftp-username', SimplyBackItUpSettingsForm.Settings?.backupStorageCredentials['ftp-username'] || '');
            this.createCredentialInput('FTP Password', 'ftp-password', SimplyBackItUpSettingsForm.Settings?.backupStorageCredentials['ftp-password'] || '');
            this.createCredentialInput('FTP Port', 'ftp-port', SimplyBackItUpSettingsForm.Settings?.backupStorageCredentials['ftp-port'] || '21');
        },

        createCredentialInput: function (labelText, inputId, defaultValue = '') {
            const label = document.createElement('label');
            label.setAttribute('for', inputId);
            label.textContent = labelText;

            const input = document.createElement('input');
            input.value = defaultValue;
            input.type = 'text';
            input.id = inputId;
            input.placeholder = `Enter ${labelText}`;

            const container = document.createElement('p');
            container.appendChild(label);
            container.appendChild(input);

            this.storageApiCredentialContainer.appendChild(container);
        },

        createLastBackUpTime: function () {
            const lastBackupTime = document.createElement('p');
            lastBackupTime.id = 'last-backup-time';
            lastBackupTime.textContent = 'Last Backup Time: ' + SimplyBackItUpSettingsForm.Settings.lastBackupTime || 'Never';
            this.container.appendChild(lastBackupTime);
        },

        updateLastBackupTime: function ($date) {
            document.getElementById('last-backup-time').textContent = 'Last Backup Time: ' + $date;
        },

        createButtons: function () {
            const saveButton = document.createElement('button');
            saveButton.id = 'save-settings';
            saveButton.className = 'button button-primary';
            saveButton.textContent = 'Save Settings';

            const backupNowButton = document.createElement('button');
            backupNowButton.id = 'backup-site';
            backupNowButton.className = 'button button-secondary';
            backupNowButton.style.marginLeft = '10px';
            backupNowButton.textContent = 'Backup Now';

            this.container.appendChild(saveButton);
            this.container.appendChild(backupNowButton);
        },

        appendContainer: function () {
            document.getElementById('simply-backitup-settings').appendChild(this.container);
        },

        createBackupNowProgress: function () {
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

        updateBackupNowProgress: function (value, message) {
            const spinner = document.createElement('div');
            spinner.className = 'spinner is-active';
            spinner.style.float = 'none';

            const progressBar = document.querySelector('#backup-now-progress .progress-bar');
            progressBar.style.width = `${value}%`;
            progressBar.setAttribute('aria-valuenow', value);
            const progressPercentage = document.getElementById('backup-now-progress-text');
            progressPercentage.innerHTML = `${value}% ` + spinner.outerHTML;

            const progressList = document.getElementById('backup-now-progress-list');
            const listItem = document.createElement('li');
            listItem.textContent = message;
            progressList.appendChild(listItem);
        },

        removeBackupNowProgress: function () {
            const progress = document.getElementById('backup-now-progress-container');
            if (progress) {
                progress.remove();
            }
        },

        performStep1: function () {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', SimplyBackItUp.ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            SimplyBackItUpSettingsForm.updateBackupNowProgress(0, 'Starting backup...');
            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success && response?.data?.progress) {
                        SimplyBackItUpSettingsForm.updateBackupNowProgress(response?.data?.progress, response?.data?.message);
                        SimplyBackItUpSettingsForm.performStep2();
                    } else {
                        SimplyBackItUpSettingsForm.updateBackupNowProgress(0, response?.data?.message);
                        SimplyBackItUpSettingsForm.removeBackupNowProgress();
                    }
                } else {
                    SimplyBackItUpSettingsForm.updateBackupNowProgress(0, 'Failed to start backup.');
                    SimplyBackItUpSettingsForm.removeBackupNowProgress();
                }
            };
            const data = new URLSearchParams();
            data.append('action', 'simply_backitup_step1');
            data.append('nonce', SimplyBackItUp.nonce);
            xhr.send(data);
        },

        performStep2: function () {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', SimplyBackItUp.ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success && response?.data?.progress) {
                        SimplyBackItUpSettingsForm.updateBackupNowProgress(response?.data?.progress, response?.data?.message);
                        SimplyBackItUpSettingsForm.performStep3();
                    } else {
                        SimplyBackItUpSettingsForm.updateBackupNowProgress(33, response?.data?.message);
                        SimplyBackItUpSettingsForm.removeBackupNowProgress();
                    }
                } else {
                    SimplyBackItUpSettingsForm.updateBackupNowProgress(33, 'Failed to export database.');
                    SimplyBackItUpSettingsForm.removeBackupNowProgress();
                }
            };
            const data = new URLSearchParams();
            data.append('action', 'simply_backitup_step2');
            data.append('nonce', SimplyBackItUp.nonce);
            xhr.send(data);
        },

        performStep3: function () {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', SimplyBackItUp.ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success && response?.data?.progress) {
                        SimplyBackItUpSettingsForm.updateBackupNowProgress(response?.data?.progress, response?.data?.message);
                        SimplyBackItUpSettingsForm.updateLastBackupTime(response?.data?.backupTime);
                        setTimeout(() => {
                            SimplyBackItUpSettingsForm.removeBackupNowProgress();
                        }, 1000);
                    } else {
                        SimplyBackItUpSettingsForm.updateBackupNowProgress(66, response?.data?.message);
                        SimplyBackItUpSettingsForm.removeBackupNowProgress();
                    }
                } else {
                    SimplyBackItUpSettingsForm.updateBackupNowProgress(66, 'Failed to upload to cloud.');
                    SimplyBackItUpSettingsForm.removeBackupNowProgress();
                }
            };
            const data = new URLSearchParams();
            data.append('action', 'simply_backitup_step3');
            data.append('nonce', SimplyBackItUp.nonce);
            xhr.send(data);
        },

        addEventListeners: function () {
            document.getElementById('backup-site').addEventListener('click', function () {
                SimplyBackItUpSettingsForm.removeBackupNowProgress();
                SimplyBackItUpSettingsForm.createBackupNowProgress();
                SimplyBackItUpSettingsForm.updateBackupNowProgress(0);
                SimplyBackItUpSettingsForm.performStep1();
            });

            document.getElementById('save-settings').addEventListener('click', function () {
                const formData = new FormData();
                formData.append('action', 'simply_backitup_save_settings');
                formData.append('nonce', SimplyBackItUp.nonce);
                formData.append('frequency', document.getElementById('backup-frequency').value);
                formData.append('time', document.getElementById('backup-time').value);
                formData.append('email', document.getElementById('backup-email').value);
                formData.append('backup-storage-location', document.getElementById('backup-storage-location').value);
                const backupStorageCredentials = JSON.stringify({
                    'google-drive-api-key': document.getElementById('google-drive-api-key')?.value || '',
                    'google-drive-client-id': document.getElementById('google-drive-client-id')?.value || '',
                    'google-drive-client-secret': document.getElementById('google-drive-client-secret')?.value || '',
                    'dropbox-access-token': document.getElementById('dropbox-access-token')?.value || '',
                    'one-drive-client-id': document.getElementById('one-drive-client-id')?.value || '',
                    'one-drive-client-secret': document.getElementById('one-drive-client-secret')?.value || '',
                    'amazon-s3-access-key': document.getElementById('amazon-s3-access-key')?.value || '',
                    'amazon-s3-secret-key': document.getElementById('amazon-s3-secret-key')?.value || '',
                    'amazon-s3-bucket-name': document.getElementById('amazon-s3-bucket-name')?.value || '',
                    'amazon-s3-region': document.getElementById('amazon-s3-region')?.value || '',
                    'ftp-host': document.getElementById('ftp-host')?.value || '',
                    'ftp-username': document.getElementById('ftp-username')?.value || '',
                    'ftp-password': document.getElementById('ftp-password')?.value || '',
                    'ftp-port': document.getElementById('ftp-port')?.value || '',
                });
                console.log(backupStorageCredentials);
                formData.append('backupStorageCredentials', backupStorageCredentials);
                var xhr = new XMLHttpRequest();
                xhr.open('POST', SimplyBackItUp.ajaxurl, true);
                xhr.onload = function () {
                    const response = JSON.parse(xhr.responseText);
                    console.log(response);
                    SimplyBackItUpSettingsForm.removeAlert();
                    if (xhr.status >= 200 && xhr.status < 300) {
                        SimplyBackItUpSettingsForm.createAlert('Settings saved.');
                    } else {
                        SimplyBackItUpSettingsForm.createAlert('Failed to save settings.', 'error');
                    }
                    setTimeout(() => {
                        SimplyBackItUpSettingsForm.removeAlert();
                    }, 5000);
                };
                xhr.send(formData);
            });
        },
    };

    if (document.getElementById('simply-backitup-settings')) {
        SimplyBackItUpSettingsForm.init();
    }
});

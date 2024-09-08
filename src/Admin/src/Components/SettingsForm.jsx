import React, { useState, useEffect } from "react";
import PropTypes from "prop-types";
import BackupProgessBar from "./BackupProgessBar";

const SettingsForm = ({ settings, ajaxUrl, nonce }) => {
	const [formSettings, setFormSettings] = useState(settings);
	const [alert, setAlert] = useState(null);
	const [backupProgress, setBackupProgress] = useState(null);
	const [backupStorageCredentials, setBackupStorageCredentials] = useState(
		settings.backupStorageCredentials || {}
	);
	const [alertTimeout, setAlertTimeout] = useState(null);
	const [lastBackupTime, setLastBackupTime] = useState(
		settings.lastBackupTime || "Never"
	);

	useEffect(() => {
		console.log("Current form settings:", formSettings);
		console.log("ajaxUrl:", ajaxUrl);
		console.log("nonce:", nonce);

		return () => {
			clearTimeout(alertTimeout);
		};
	}, [formSettings]);

	const createAlert = (message, type = "success") => {
		setAlert({ message, type });
		clearTimeout(alertTimeout);
		setAlertTimeout(
			setTimeout(() => {
				setAlert(null);
			}, 5000)
		);
	};

	const handleChange = (e) => {
		const { id, value } = e.target;
		setFormSettings((prevSettings) => ({
			...prevSettings,
			[id]: value,
		}));
	};

	const handleCredentialChange = (e) => {
		const { id, value } = e.target;
		setBackupStorageCredentials((prevCredentials) => ({
			...prevCredentials,
			[id]: value,
		}));
	};

	const handleSubmit = (e) => {
		e.preventDefault();

		const newFormSettings = { ...formSettings };
		for (const key in formSettings) {
			if (e.target[key]) {
				newFormSettings[key] = e.target[key].value;
			}
		}

		const formData = new FormData();
		formData.append("action", "simply_backitup_save_settings");
		formData.append("nonce", nonce);
		for (const key in newFormSettings) {
			switch (key) {
				case "backupStorageCredentials":
					formData.append(
						"backupStorageCredentials",
						JSON.stringify(backupStorageCredentials)
					);
					break;
				default:
					formData.append(key, newFormSettings[key]);
					break;
			}
		}

		fetch(ajaxUrl, {
			method: "POST",
			body: formData,
		})
			.then((res) => res.json())
			.then((res) => {
				if (res?.success && res?.success === true) {
					setFormSettings(newFormSettings);
					createAlert("Settings saved successfully.", "success");
				} else {
					createAlert("Failed to save settings.", "error");
				}
			})
			.catch((error) => {
				console.error("Error:", error);
				createAlert("An error occurred while saving settings.", "error");
			});
	};

	const handleBackupNow = async () => {
		removeBackupNowProgress();
		createBackupNowProgress();
		try {
			await performBackupStep(
				"simply_backitup_step1",
				33,
				"Creating backup file..."
			);
			await performBackupStep(
				"simply_backitup_step2",
				66,
				"Uploading backup file..."
			);
			await performBackupStep(
				"simply_backitup_step3",
				100,
				"Backup completed."
			);
			createAlert("Backup completed successfully.", "success");
            setTimeout(() => {
                removeBackupNowProgress();
            }, 2000);
		} catch (error) {
			console.error("Error during backup process:", error);
			removeBackupNowProgress();
			createAlert("An error occurred during backup.", "error");
		}
	};

	const performBackupStep = async (action, progressValue, message) => {
		const data = new URLSearchParams();
		data.append("action", action);
		data.append("nonce", nonce);

		const response = await fetch(ajaxUrl, {
			method: "POST",
			headers: {
				"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
			},
			body: data,
		});

		const responseData = await response.json();
		if (responseData.success) {
			updateBackupNowProgress(
				progressValue,
				responseData?.data?.message || message,
                false
			);
			if (progressValue === 100 && responseData?.data?.backupTime) {
				setLastBackupTime(responseData.data.backupTime);
			}
		} else {
			throw new Error(
				responseData?.data?.message || "Failed to perform backup step."
			);
		}
	};

	const createBackupNowProgress = () => {
		setBackupProgress({
			value: 0,
			message: "Starting backup...",
		});
	};

	const updateBackupNowProgress = (value, message, failed) => {
		setBackupProgress({
			value,
			message,
            failed,
		});
	};

	const removeBackupNowProgress = () => {
		setBackupProgress(null);
	};

	const renderCredentialFields = (location) => {
		switch (location) {
			case "Google Drive":
				return renderGoogleDriveFields();
			case "Dropbox":
				return renderDropboxFields();
			case "OneDrive":
				return renderOneDriveFields();
			case "Amazon S3":
				return renderAmazonS3Fields();
			case "FTP":
				return renderFTPFields();
			default:
				return null;
		}
	};

	const renderGoogleDriveFields = () => (
		<>
			{renderCredentialInput("Google Drive API Key", "google-drive-api-key")}
			{renderCredentialInput(
				"Google Drive Client ID",
				"google-drive-client-id"
			)}
			{renderCredentialInput(
				"Google Drive Client Secret",
				"google-drive-client-secret"
			)}
		</>
	);

	const renderDropboxFields = () => (
		<>{renderCredentialInput("Dropbox Access Token", "dropbox-access-token")}</>
	);

	const renderOneDriveFields = () => (
		<>
			{renderCredentialInput("OneDrive Client ID", "one-drive-client-id")}
			{renderCredentialInput(
				"OneDrive Client Secret",
				"one-drive-client-secret"
			)}
		</>
	);

	const renderAmazonS3Fields = () => (
		<>
			{renderCredentialInput("Amazon S3 Access Key", "amazon-s3-access-key")}
			{renderCredentialInput("Amazon S3 Secret Key", "amazon-s3-secret-key")}
			{renderCredentialInput("Amazon S3 Bucket Name", "amazon-s3-bucket-name")}
			{renderCredentialInput("Amazon S3 Region", "amazon-s3-region")}
		</>
	);

	const renderFTPFields = () => (
		<>
			{renderCredentialInput("FTP Host", "ftp-host")}
			{renderCredentialInput("FTP Username", "ftp-username")}
			{renderCredentialInput("FTP Password", "ftp-password")}
			{renderCredentialInput("FTP Port", "ftp-port", "21")}
		</>
	);

	const renderCredentialInput = (labelText, inputId, defaultValue = "") => (
		<p key={inputId}>
			<label htmlFor={inputId}>{labelText}</label>
			<input
				type="text"
				id={inputId}
				value={backupStorageCredentials[inputId] || defaultValue}
				onChange={handleCredentialChange}
			/>
		</p>
	);

	return (
		<div className="wrap">
			<h2>Backup Settings</h2>

			{alert && (
				<div className={`notice notice-${alert.type}`}>
					<p>{alert.message}</p>
				</div>
			)}

			<form onSubmit={handleSubmit}>
				{/* Frequency Setting */}
				<p>
					<label htmlFor="backup-frequency">Backup Frequency</label>
					<select
						id="backup-frequency"
						name="backup-frequency"
						value={formSettings["backup-frequency"] || "daily"}
						onChange={handleChange}
					>
						<option value="daily">Daily</option>
						<option value="weekly">Weekly</option>
						<option value="monthly">Monthly</option>
					</select>
				</p>

				{/* Time Setting */}
				<p>
					<label htmlFor="backup-time">Backup Time</label>
					<input
						type="time"
						id="backup-time"
						name="backup-time"
						value={formSettings["backup-time"] || "03:00"}
						onChange={handleChange}
					/>
				</p>

				{/* Email Setting */}
				<p>
					<label htmlFor="backup-email">Backup Email</label>
					<input
						id="backup-email"
						name="backup-email"
						value={formSettings["backup-email"] || ""}
						onChange={handleChange}
					/>
				</p>

				{/* Storage Location Setting */}
				<p>
					<label htmlFor="backup-storage-location">Storage Location</label>
					<select
						id="backup-storage-location"
						name="backup-storage-location"
						value={formSettings["backup-storage-location"] || ""}
						onChange={handleChange}
					>
						<option value="">Select</option>
						<option value="Google Drive">Google Drive</option>
						<option value="Dropbox">Dropbox</option>
						<option value="OneDrive">OneDrive</option>
						<option value="Amazon S3">Amazon S3</option>
						<option value="FTP">FTP</option>
					</select>
				</p>

				{/* Render Credential Fields Based on Selection */}
				{renderCredentialFields(formSettings["backup-storage-location"] || "")}

				{/* Buttons */}
				<button
					type="submit"
					className="button button-primary"
				>
					Save Settings
				</button>
				<button
					type="button"
					className="button button-secondary"
					style={{ marginLeft: "10px" }}
					onClick={handleBackupNow}
				>
					Backup Now
				</button>
			</form>

			{/* Backup Progress */}
			{backupProgress && <BackupProgessBar {...backupProgress} />}
		</div>
	);
};

SettingsForm.propTypes = {
	settings: PropTypes.shape({
		frequency: PropTypes.string,
		time: PropTypes.string,
		email: PropTypes.string,
		backupStorageLocation: PropTypes.string,
		nonce: PropTypes.string,
		backupStorageCredentials: PropTypes.object,
	}).isRequired,
	ajaxUrl: PropTypes.string.isRequired,
	nonce: PropTypes.string.isRequired,
};

export default SettingsForm;

const BackupProgessBar = ({ value, message }) => {
	return (
		<div id="backup-now-progress-container">
			<div
				id="backup-now-progress"
				className="progress"
			>
				<div
					className="progress-bar"
					role="progressbar"
					aria-valuemin="0"
					aria-valuemax="100"
					aria-valuenow={value}
					style={{
						width: `${value}%`,
						backgroundColor: value < 100 ? "#0073aa" : "#46b450",
					}}
				></div>
			</div>
			<div
				id="backup-now-progress-text"
				className="progress-bar-text"
			>
				<div
					className={value < 100 ? "spinner is-active" : ""}
					style={{ display: "inline-block", marginLeft: "5px" }}
				></div>
				{message}
			</div>
		</div>
	);
};

export default BackupProgessBar;
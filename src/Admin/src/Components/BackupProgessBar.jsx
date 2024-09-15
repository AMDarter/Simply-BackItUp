import { Progress } from "@chakra-ui/react";

const BackupProgessBar = ({ value, message }) => {
	return (
		<div id="backup-now-progress-container">
			<Progress
				value={value}
				colorScheme={value < 100 ? "blue" : "green"}
				max={100}
				min={0}
				size="md"
			></Progress>
			<div
				className={value < 100 ? "spinner is-active" : ""}
				style={{ display: "inline-block" }}
			></div>
			{message}
		</div>
	);
};

export default BackupProgessBar;

import { Progress, Spinner, Center, HStack } from "@chakra-ui/react";

const BackupProgressBar = ({ value, message }) => {
	return (
		<div id="backup-now-progress-container">
			<Progress
				value={value}
				colorScheme={value < 100 ? "blue" : "green"}
				max={100}
				min={0}
				size="md"
                style={{ borderRadius: "5px" }}
			></Progress>
			<HStack mt={3}>
				<Center>
					<Spinner />
					<span style={{ marginLeft: "5px" }}>{message}</span>
				</Center>
			</HStack>
		</div>
	);
};

export default BackupProgressBar;

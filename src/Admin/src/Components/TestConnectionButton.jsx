import { Button } from "@chakra-ui/react";

const TestConnectionButton = ({ platform, ...props }) => {
	return (
		<Button
			type="button"
			className={"button button-secondary" + (props?.disabled ? " disabled" : "")}
			style={{ marginTop: "10px" }}
            {...props}
		>
			Test {platform} Connection
		</Button>
	);
};

export default TestConnectionButton;
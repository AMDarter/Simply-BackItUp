import { Button } from "@chakra-ui/react";

const TestConnectionButton = ({ platform, ...props }) => {
	return (
		<Button
			type="button"
            size='sm'
			colorScheme="blue"
            variant='outline'
			style={{ marginTop: "10px" }}
            {...props}
		>
			Test {platform} Connection
		</Button>
	);
};

export default TestConnectionButton;
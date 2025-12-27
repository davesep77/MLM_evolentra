const hre = require("hardhat");

async function main() {
    console.log("Deploying EvolentraToken to BSC...");

    // Get the contract factory
    const EvolentraToken = await hre.ethers.getContractFactory("EvolentraToken");

    // Deploy the contract
    const token = await EvolentraToken.deploy();
    await token.deployed();

    console.log("EvolentraToken deployed to:", token.address);
    console.log("Transaction hash:", token.deployTransaction.hash);

    // Wait for a few block confirmations
    console.log("Waiting for block confirmations...");
    await token.deployTransaction.wait(5);

    // Verify contract on BscScan (if API key is set)
    if (process.env.BSCSCAN_API_KEY) {
        console.log("Verifying contract on BscScan...");
        try {
            await hre.run("verify:verify", {
                address: token.address,
                constructorArguments: []
            });
            console.log("Contract verified successfully");
        } catch (error) {
            console.log("Verification failed:", error.message);
        }
    }

    // Save deployment info
    const fs = require('fs');
    const deploymentInfo = {
        network: hre.network.name,
        contractAddress: token.address,
        deployer: (await hre.ethers.getSigners())[0].address,
        timestamp: new Date().toISOString(),
        transactionHash: token.deployTransaction.hash
    };

    fs.writeFileSync(
        'deployment.json',
        JSON.stringify(deploymentInfo, null, 2)
    );

    console.log("\nDeployment info saved to deployment.json");
    console.log("\nNext steps:");
    console.log("1. Update web3_integration.js with contract address:", token.address);
    console.log("2. Update the frontend to use the deployed contract");
    console.log("3. Test all contract functions on testnet before mainnet deployment");
}

main()
    .then(() => process.exit(0))
    .catch((error) => {
        console.error(error);
        process.exit(1);
    });

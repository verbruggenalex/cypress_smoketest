String apiKey = System.getenv("APPLITOOLS_API_KEY");
eyes.setApiKey(apiKey);

module.exports = {
  testConcurrency: 1,
  apiKey: apiKey,
  browser: [
    // Add browsers with different viewports
    {width: 1024, height: 768, name: 'chrome'}
  ],
  // set batch name to the configuration
  batchName: 'Ultrafast Batch'
}

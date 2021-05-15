module.exports = {
  testConcurrency: 1,
  apiKey: 'APPLITOOLS_API_KEY',
  browser: [
    // Add browsers with different viewports
    {width: 1024, height: 768, name: 'chrome'}
  ],
  // set batch name to the configuration
  batchName: 'Ultrafast Batch'
}

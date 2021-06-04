String apiKey = System.getenv("APPLITOOLS_API_KEY");
eyes.setApiKey(apiKey);

module.exports = {
  ignoreBaseline: false,
  testConcurrency: 1,
  apiKey: apiKey,
  browser: [
    {width: 1024, height: 768, name: 'firefox'},
    {width: 1024, height: 768, name: 'chrome'},
  ],
}

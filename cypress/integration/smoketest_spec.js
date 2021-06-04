/// <reference types="cypress" />
describe('Smoketest on list of urls', () => {

  let startTime

  before(() => {
    // This endpoint is provided by this module to automatically login with a certain role.
    // If no user is in the database yet, one will be created.
    // @todo: provide security that this can never be used on production!
    cy.request('http://web/cypress_smoketest/login/administrator')
  })

  beforeEach(function () {
    startTime = Math.floor(Date.now()/1000)
    cy.preserveAllCookiesOnce()
  });

  // Visit all urls from the fixture.
  const items = require('../fixtures/smoke-single')
  items.forEach((item) => {
    it(`Visit ${item.url}`, () => {
      // Check if response status matches.
      cy.request({
        url: item.url,
        failOnStatusCode: false
      }).then((resp) => {
        let status = item.hasOwnProperty('status') ? item.status : 200
        expect(resp.status).to.eq(status)
      })
      // Visit the url.
      cy.visit({
        url: item.url,
        failOnStatusCode: false
      })
      // Applitools
      cy.eyesOpen({
        appName: 'Drupal',
        testName: 'Adminstrator on ' + item.url,
        // For some reason this does not work when put in config.
        // There it will only take screenshots for chrome.
        browser: [
          {width: 1024, height: 768, name: 'firefox'},
          {width: 1024, height: 768, name: 'chrome'},
        ],
      })

      cy.eyesCheckWindow({
        tag: item.url,
        target: 'window',
        fully: true
      });

      cy.eyesClose()
      // If we have exceptions check if we expect them.
      cy.on('uncaught:exception', (err, runnable) => {
        if (item.hasOwnProperty('jsallowregex')) {
          let regexp = new RegExp(item.jsallowregex);
          expect(err.message).to.match(regexp)
          return false
        }
      })
      // Check if watchdog is what we expect it to be.
      cy.request('/cypress_smoketest/watchdog/' + startTime + '/' + Math.floor(Date.now()/1000)).then(
        (response) => {
          let watchdog = JSON.parse(response.body)
          if (watchdog.length > 0) {
            watchdog.forEach(function(entry) {
              if (item.hasOwnProperty('phpallowregex')) {
                let regexp = new RegExp(item.phpallowregex);
                expect(entry.message).to.match(regexp)
              }
              else {
                expect(entry.message).to.not.exist
              }
            });
          }
        }
      )
    })
  })

})

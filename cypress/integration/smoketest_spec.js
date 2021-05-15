/// <reference types="cypress" />
describe('Smoketest on list of urls', () => {
  // this example fetches list of 3 users from the server
  // and then creates 3 separate tests to check something about each user

  before(() => {
    cy.request('http://web/cypress_smoketest/login/administrator')
  })

  // commands.js
  Cypress.Commands.add('preserveAllCookiesOnce', () => {
    cy.getCookies().then(cookies => {
      const namesOfCookies = cookies.map(c => c.name)
      Cypress.Cookies.preserveOnce(...namesOfCookies)
    })
  })

  // your-suite.test.js
  beforeEach(function () {
    cy.preserveAllCookiesOnce()
  });

  const urls = require('../fixtures/urls')
  urls.forEach((url) => {
    it(`Visit ${url}`, () => {
      cy.visit(url)
      // Call Open on eyes to initialize a test session
      cy.eyesOpen({
        appName: 'Drupal',
        testName: 'Smoketest',
      })

      cy.eyesCheckWindow({
        tag: url,
        target: 'window',
        fully: true
      });

      //cy.eyesClose()
    })
  })

})

/// <reference types="cypress" />
describe('Smoketest on list of urls', () => {

  before(() => {
    // This endpoint is provided by this module to automatically login with a certain role.
    // If no user is in the database yet, one will be created.
    // @todo: provide security that this can never be used on production!
    cy.request('http://web/cypress_smoketest/login/administrator')
  })

  beforeEach(function () {
    cy.preserveAllCookiesOnce()
  });

  // Visit all urls from the fixture.
  const urls = require('../fixtures/urls')
  urls.forEach((url) => {
    it(`Visit ${url}`, () => {
      cy.visit(url)
    })
  })

})

/**
 * Mail7 Email Validation for Google Sheets.
 *
 * Honest classification: results are "Valid", "Not Valid", or "Unknown". Unknown means the
 * address could not be verified (catch-all, greylisting, disposable) - it is NOT the same as
 * invalid, so you never wrongly reject a real person.
 *
 * Two ways to use it:
 *   - Cell formula:  =MAIL7(A2)              -> "Valid" / "Not Valid" / "Unknown"
 *   - Menu:          Mail7 -> Validate selected column   (writes status to the next column)
 *
 * An API key is optional (free anonymous tier without it). For validating many rows, set a
 * key via "Mail7 -> Set API key" to avoid the anonymous rate limit.
 */

var MAIL7_BASE_URL = 'https://mail7.net/api';

/**
 * Validate an email address with Mail7.
 *
 * @param {string} email The email address to validate.
 * @return {string} "Valid", "Not Valid", "Unknown", or "" for a blank cell.
 * @customfunction
 */
function MAIL7(email) {
  if (!email) {
    return '';
  }
  var result = mail7Validate_(String(email).trim());
  return result && result.status ? result.status : 'Unknown';
}

/** Low-level call to the Mail7 API. Returns the parsed result object, or null on error. */
function mail7Validate_(email) {
  if (!email) {
    return null;
  }
  var key = getApiKey_();
  var options = {
    method: 'post',
    contentType: 'application/json',
    payload: JSON.stringify({ email: email }),
    muteHttpExceptions: true,
  };
  if (key) {
    options.headers = { 'X-API-Key': key };
  }
  try {
    var response = UrlFetchApp.fetch(MAIL7_BASE_URL + '/validate-single', options);
    if (response.getResponseCode() !== 200) {
      return null; // fail open
    }
    return JSON.parse(response.getContentText());
  } catch (e) {
    return null; // fail open
  }
}

function getApiKey_() {
  return PropertiesService.getScriptProperties().getProperty('MAIL7_API_KEY') || '';
}

function onOpen() {
  SpreadsheetApp.getUi()
    .createMenu('Mail7')
    .addItem('Validate selected column', 'mail7ValidateColumn')
    .addSeparator()
    .addItem('Set API key', 'mail7SetApiKey')
    .addToUi();
}

function onInstall() {
  onOpen();
}

function mail7SetApiKey() {
  var ui = SpreadsheetApp.getUi();
  var response = ui.prompt(
    'Mail7 API key',
    'Optional - leave empty to use the free anonymous tier. Get a key at mail7.net.',
    ui.ButtonSet.OK_CANCEL
  );
  if (response.getSelectedButton() === ui.Button.OK) {
    PropertiesService.getScriptProperties().setProperty('MAIL7_API_KEY', response.getResponseText().trim());
    ui.alert('Mail7 API key saved.');
  }
}

/**
 * Validate every email in the selected column and write the status to the column on its right.
 * Select a single column of email addresses first, then run this.
 */
function mail7ValidateColumn() {
  var sheet = SpreadsheetApp.getActiveSheet();
  var range = sheet.getActiveRange();
  var values = range.getValues();
  var out = [];

  for (var i = 0; i < values.length; i++) {
    var email = String(values[i][0] || '').trim();
    if (!email) {
      out.push(['']);
      continue;
    }
    var result = mail7Validate_(email);
    var status = result && result.status ? result.status : 'Unknown';
    var disposable = result && result.is_disposable ? ' (disposable)' : '';
    out.push([status + disposable]);
    Utilities.sleep(150); // be gentle on the API
  }

  sheet.getRange(range.getRow(), range.getColumn() + 1, out.length, 1).setValues(out);
}

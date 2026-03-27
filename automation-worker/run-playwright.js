#!/usr/bin/env node
"use strict";

const FIELD_RULES = [
  { key: "email", label: "E-mail", regex: /(^|[^a-z])(e-?mail)([^a-z]|$)/i },
  { key: "cpf", label: "CPF", regex: /(^|[^a-z])(cpf|tax\s*id|documento)([^a-z]|$)/i },
  { key: "rg", label: "RG", regex: /(^|[^a-z])(rg|registro\s*geral)([^a-z]|$)/i },
  {
    key: "birth_date",
    label: "Data de nascimento",
    regex: /(birth|nascimento|data.*nasc|dob|date.*birth)/i
  },
  { key: "whatsapp", label: "Whatsapp", regex: /(whats|whatsapp)/i },
  { key: "phone", label: "Telefone", regex: /(phone|telefone|celular|mobile|tel)/i },
  { key: "instagram", label: "Instagram", regex: /(instagram|insta)/i },
  { key: "zip_code", label: "CEP", regex: /(cep|zip|postal|postcode)/i },
  { key: "street", label: "Rua", regex: /(street|rua|address|endereco|logradouro)/i },
  { key: "number", label: "Numero", regex: /(number|numero|num|nro)/i },
  { key: "complement", label: "Complemento", regex: /(complement|apt|apto|suite|bloco)/i },
  { key: "neighborhood", label: "Bairro", regex: /(bairro|district|neighborhood)/i },
  { key: "city", label: "Cidade", regex: /(city|cidade|municipio)/i },
  { key: "state", label: "UF", regex: /(state|estado|uf|province|region)/i },
  { key: "country", label: "Pais", regex: /(country|pais)/i },
  { key: "first_name", label: "Nome", regex: /(first\s*name|nome)/i },
  { key: "last_name", label: "Sobrenome", regex: /(last\s*name|surname|sobrenome)/i },
  {
    key: "full_name",
    label: "Nome completo",
    regex: /(full\s*name|nome\s*completo|nome e sobrenome)/i
  }
];

const COOKIE_REJECT_PATTERNS = [
  /rejeitar/i,
  /recusar/i,
  /apenas necessa/i,
  /somente necessa/i,
  /only necessary/i,
  /reject/i,
  /decline/i
];

const SUBMIT_PATTERNS = [
  /cadastrar/i,
  /participar/i,
  /enviar/i,
  /finalizar/i,
  /continuar/i,
  /submit/i,
  /register/i,
  /join/i
];

async function main() {
  const payload = await readPayload();
  const logs = [];

  if (!payload || typeof payload.url !== "string" || payload.url.trim() === "") {
    return failureResult(
      "Payload invalido para automacao.",
      logs,
      { reason: "invalid_payload" }
    );
  }

  const profileData = buildProfileData(payload.profile || {});
  const preferences = buildPreferenceData(payload.preferences || {});
  const options = buildOptions(payload.options || {});

  let playwright;
  try {
    playwright = require("playwright");
  } catch (error) {
    return failureResult(
      "Dependencia Playwright nao instalada. Rode npm install em automation-worker.",
      logs,
      { reason: "playwright_missing", error: safeErrorMessage(error) }
    );
  }

  const { chromium } = playwright;
  let browser;
  let context;
  let page;

  try {
    browser = await chromium.launch({
      headless: options.headless
    });

    context = await browser.newContext({
      userAgent:
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122 Safari/537.36"
    });
    page = await context.newPage();

    logInfo(logs, "Acessando pagina da promocao.", { url: payload.url });
    await page.goto(payload.url, {
      waitUntil: "domcontentloaded",
      timeout: options.navigationTimeoutMs
    });
    await page.waitForTimeout(1200);

    if (preferences.auto_reject_cookies) {
      const cookieAction = await tryRejectCookies(page, options.actionTimeoutMs);
      if (cookieAction) {
        logInfo(logs, "Banner de cookies tratado automaticamente.", {
          action: cookieAction
        });
      }
    }

    const captchaBeforeFill = await detectCaptcha(page);
    if (captchaBeforeFill && preferences.pause_on_captcha) {
      logWarn(logs, "Captcha detectado antes do preenchimento.");
      return {
        status: "captcha_required",
        message: "Captcha detectado. Finalize manualmente no site da promocao.",
        missing_fields: [],
        metadata: await buildMetadata(page, {
          phase: "before_fill",
          filled_keys: []
        }),
        logs
      };
    }

    const fillResult = await fillFormFields(page, profileData, preferences, options.actionTimeoutMs);

    if (fillResult.missingFields.length > 0) {
      logWarn(logs, "Campos obrigatorios pendentes para finalizar cadastro.", {
        missing_fields: fillResult.missingFields
      });

      return {
        status: "needs_info",
        message: "Alguns campos obrigatorios nao puderam ser preenchidos automaticamente.",
        missing_fields: fillResult.missingFields,
        metadata: await buildMetadata(page, {
          phase: "fill",
          filled_keys: fillResult.filledKeys
        }),
        logs: logs.concat(fillResult.logs)
      };
    }

    const captchaAfterFill = await detectCaptcha(page);
    if (captchaAfterFill && preferences.pause_on_captcha) {
      logWarn(logs, "Captcha detectado apos preenchimento.");
      return {
        status: "captcha_required",
        message: "Captcha detectado apos preencher o formulario. Continue manualmente.",
        missing_fields: [],
        metadata: await buildMetadata(page, {
          phase: "after_fill",
          filled_keys: fillResult.filledKeys
        }),
        logs: logs.concat(fillResult.logs)
      };
    }

    const submitAction = await attemptSubmit(page, options.actionTimeoutMs);
    if (submitAction) {
      logInfo(logs, "Botao de envio acionado.", { button: submitAction });
      await page.waitForTimeout(2500);
    } else {
      logWarn(logs, "Nenhum botao de envio identificado automaticamente.");
    }

    const captchaAfterSubmit = await detectCaptcha(page);
    if (captchaAfterSubmit && preferences.pause_on_captcha) {
      logWarn(logs, "Captcha detectado apos tentativa de envio.");
      return {
        status: "captcha_required",
        message: "Captcha detectado na finalizacao. Continue manualmente.",
        missing_fields: [],
        metadata: await buildMetadata(page, {
          phase: "after_submit",
          filled_keys: fillResult.filledKeys
        }),
        logs: logs.concat(fillResult.logs)
      };
    }

    const completionMessage = submitAction
      ? "Formulario preenchido e tentativa de envio executada."
      : "Formulario preenchido. Confira o envio manualmente, pois nao foi possivel identificar o botao de envio.";

    return {
      status: "completed",
      message: completionMessage,
      missing_fields: [],
      metadata: await buildMetadata(page, {
        phase: "completed",
        filled_keys: fillResult.filledKeys,
        submit_button: submitAction || null
      }),
      logs: logs.concat(fillResult.logs)
    };
  } catch (error) {
    const errorMessage = safeErrorMessage(error);
    let userMessage = "Falha ao executar automacao Playwright.";

    if (errorMessage.toLowerCase().includes("executable doesn't exist")) {
      userMessage = "Navegador do Playwright nao instalado. Rode: npx playwright install chromium";
    }

    if (errorMessage.toLowerCase().includes("net::err_name_not_resolved")) {
      userMessage = "Nao foi possivel abrir o link da promocao. Verifique a URL.";
    }

    logError(logs, "Erro no worker Playwright.", { error: errorMessage });

    return failureResult(userMessage, logs, {
      reason: "runtime_error",
      error: errorMessage
    });
  } finally {
    if (context) {
      await context.close().catch(() => null);
    }
    if (browser) {
      await browser.close().catch(() => null);
    }
  }
}

function buildProfileData(profile) {
  const fullName = sanitize(profile.full_name);
  const names = fullName.split(/\s+/).filter(Boolean);
  const firstName = sanitize(names[0] || "");
  const lastName = sanitize(names.slice(1).join(" ") || names[0] || "");

  return {
    full_name: fullName,
    first_name: firstName,
    last_name: lastName,
    email: sanitize(profile.email),
    cpf: sanitize(profile.cpf),
    rg: sanitize(profile.rg),
    birth_date: sanitize(profile.birth_date),
    phone: sanitize(profile.phone || profile.whatsapp),
    whatsapp: sanitize(profile.whatsapp || profile.phone),
    instagram: sanitize(profile.instagram),
    zip_code: sanitize(profile.zip_code),
    street: sanitize(profile.street),
    number: sanitize(profile.number),
    complement: sanitize(profile.complement),
    neighborhood: sanitize(profile.neighborhood),
    city: sanitize(profile.city),
    state: sanitize(profile.state).toUpperCase(),
    country: sanitize(profile.country)
  };
}

function buildPreferenceData(preferences) {
  return {
    accept_terms: Boolean(preferences.accept_terms),
    allow_marketing_emails: Boolean(preferences.allow_marketing_emails),
    allow_marketing_sms: Boolean(preferences.allow_marketing_sms),
    allow_third_party_share: Boolean(preferences.allow_third_party_share),
    receive_newsletter: Boolean(preferences.receive_newsletter),
    auto_reject_cookies: preferences.auto_reject_cookies !== false,
    pause_on_captcha: preferences.pause_on_captcha !== false
  };
}

function buildOptions(options) {
  return {
    headless: options.headless !== false,
    navigationTimeoutMs: toPositiveInt(options.navigation_timeout_ms, 45000),
    actionTimeoutMs: toPositiveInt(options.action_timeout_ms, 6000)
  };
}

async function fillFormFields(page, profileData, preferences, actionTimeoutMs) {
  const logs = [];
  const filledKeys = [];
  const missingFields = new Set();
  const locator = page.locator("input, textarea, select");
  const count = await locator.count();

  for (let i = 0; i < count; i += 1) {
    const field = locator.nth(i);
    const visible = await field.isVisible().catch(() => false);
    if (!visible) {
      continue;
    }

    const meta = await readFieldMeta(field);
    if (!meta || shouldSkipField(meta)) {
      continue;
    }

    const signature = buildSignature(meta);
    const key = resolveFieldKey(signature);

    if (meta.type === "checkbox" || meta.type === "radio") {
      const checkboxHandled = await handleCheckboxPreference(field, meta, preferences, actionTimeoutMs);

      if (meta.required) {
        const filled = await isBooleanFieldFilled(field, meta);
        if (!filled) {
          missingFields.add(resolveMissingLabel(meta, key));
        }
      }

      if (checkboxHandled) {
        logs.push({
          level: "info",
          message: "Preferencia aplicada em campo de opcao.",
          context: { field: meta.name || meta.id || meta.labelText }
        });
      }

      continue;
    }

    if (!key) {
      if (meta.required) {
        const hasValue = await hasFieldValue(field, meta);
        if (!hasValue) {
          missingFields.add(resolveMissingLabel(meta, key));
        }
      }
      continue;
    }

    const dataValue = profileData[key];

    if (!dataValue) {
      if (meta.required) {
        missingFields.add(resolveMissingLabel(meta, key));
      }
      continue;
    }

    const fillSuccess = await fillFieldValue(field, meta, dataValue, actionTimeoutMs);

    if (fillSuccess) {
      filledKeys.push(key);
    } else if (meta.required) {
      missingFields.add(resolveMissingLabel(meta, key));
    }

    const hasValueNow = await hasFieldValue(field, meta);
    if (meta.required && !hasValueNow) {
      missingFields.add(resolveMissingLabel(meta, key));
    }
  }

  return {
    logs,
    filledKeys: uniqueList(filledKeys),
    missingFields: uniqueList(Array.from(missingFields))
  };
}

async function readFieldMeta(field) {
  return field
    .evaluate((el) => {
      const attr = (name) => (el.getAttribute(name) || "").trim();

      let labelText = "";
      const id = attr("id");
      if (id) {
        const safeId = typeof CSS !== "undefined" && CSS.escape ? CSS.escape(id) : id;
        const linkedLabel = document.querySelector(`label[for="${safeId}"]`);
        if (linkedLabel) {
          labelText = (linkedLabel.innerText || linkedLabel.textContent || "").trim();
        }
      }

      if (!labelText) {
        const parentLabel = el.closest("label");
        if (parentLabel) {
          labelText = (parentLabel.innerText || parentLabel.textContent || "").trim();
        }
      }

      return {
        tag: el.tagName.toLowerCase(),
        type: (attr("type") || "").toLowerCase(),
        name: attr("name"),
        id,
        placeholder: attr("placeholder"),
        ariaLabel: attr("aria-label"),
        autocomplete: attr("autocomplete"),
        required: el.required === true,
        disabled: el.disabled === true,
        readOnly: el.readOnly === true,
        labelText: labelText.replace(/\s+/g, " ").trim()
      };
    })
    .catch(() => null);
}

function shouldSkipField(meta) {
  if (!meta) {
    return true;
  }

  if (meta.disabled || meta.readOnly) {
    return true;
  }

  const skipTypes = new Set(["hidden", "button", "submit", "reset", "image", "file"]);
  if (meta.tag === "input" && skipTypes.has(meta.type)) {
    return true;
  }

  return false;
}

function buildSignature(meta) {
  return [
    meta.name,
    meta.id,
    meta.placeholder,
    meta.ariaLabel,
    meta.autocomplete,
    meta.labelText
  ]
    .filter(Boolean)
    .join(" ")
    .toLowerCase();
}

function resolveFieldKey(signature) {
  for (const rule of FIELD_RULES) {
    if (rule.regex.test(signature)) {
      return rule.key;
    }
  }

  return null;
}

async function handleCheckboxPreference(field, meta, preferences, actionTimeoutMs) {
  const signature = buildSignature(meta);
  const value = resolveCheckboxValue(signature, preferences);

  if (value === null) {
    return false;
  }

  const checked = await field.isChecked().catch(() => false);

  if (value && !checked) {
    await field.check({ timeout: actionTimeoutMs }).catch(() => null);
  }

  if (!value && checked) {
    await field.uncheck({ timeout: actionTimeoutMs }).catch(() => null);
  }

  return true;
}

function resolveCheckboxValue(signature, preferences) {
  if (/termo|terms|aceito|concordo|politica|privacy|lgpd/i.test(signature)) {
    return preferences.accept_terms;
  }

  if (/newsletter|novidades|news|ofertas|promoc/i.test(signature)) {
    return preferences.receive_newsletter;
  }

  if (/(email|e-mail)/i.test(signature) && /(marketing|promo|oferta|comunic)/i.test(signature)) {
    return preferences.allow_marketing_emails;
  }

  if (/(sms|whatsapp|telefone|celular)/i.test(signature) && /(marketing|promo|oferta|comunic)/i.test(signature)) {
    return preferences.allow_marketing_sms;
  }

  if (/parceir|third|terceir|compartilh/i.test(signature)) {
    return preferences.allow_third_party_share;
  }

  return null;
}

async function fillFieldValue(field, meta, dataValue, actionTimeoutMs) {
  const value = sanitize(dataValue);
  if (!value) {
    return false;
  }

  try {
    if (meta.tag === "select") {
      return await selectFieldOption(field, value);
    }

    if (meta.type === "date") {
      await field.fill(normalizeDate(value), { timeout: actionTimeoutMs });
      return true;
    }

    await field.fill(value, { timeout: actionTimeoutMs });
    return true;
  } catch (_error) {
    return false;
  }
}

async function selectFieldOption(field, rawValue) {
  const value = sanitize(rawValue);
  const tries = [
    { value },
    { label: value },
    { value: value.toUpperCase() },
    { label: value.toUpperCase() }
  ];

  for (const option of tries) {
    try {
      await field.selectOption(option);
      return true;
    } catch (_error) {
      // continue
    }
  }

  return false;
}

async function hasFieldValue(field, meta) {
  if (meta.type === "checkbox" || meta.type === "radio") {
    return isBooleanFieldFilled(field, meta);
  }

  const value = await field.inputValue().catch(() => "");
  return sanitize(value) !== "";
}

async function isBooleanFieldFilled(field, meta) {
  if (meta.type !== "checkbox" && meta.type !== "radio") {
    return true;
  }

  return field.isChecked().catch(() => false);
}

async function detectCaptcha(page) {
  return page
    .evaluate(() => {
      const selectors = [
        'iframe[src*="recaptcha"]',
        'iframe[src*="hcaptcha"]',
        ".g-recaptcha",
        '[class*="captcha"]',
        '[id*="captcha"]'
      ];

      if (selectors.some((selector) => document.querySelector(selector))) {
        return true;
      }

      const text = (document.body?.innerText || "").toLowerCase();
      return /(captcha|i am not a robot|nao sou um robo|recaptcha|hcaptcha)/.test(text);
    })
    .catch(() => false);
}

async function tryRejectCookies(page, actionTimeoutMs) {
  const byRole = await clickByRolePatterns(page, COOKIE_REJECT_PATTERNS, actionTimeoutMs);
  if (byRole) {
    return byRole;
  }

  const locator = page.locator('button, [role="button"], input[type="button"], input[type="submit"]');
  const count = await locator.count();

  for (let i = 0; i < count; i += 1) {
    const button = locator.nth(i);
    const visible = await button.isVisible().catch(() => false);
    if (!visible) {
      continue;
    }

    const text = await buttonText(button);
    if (COOKIE_REJECT_PATTERNS.some((regex) => regex.test(text))) {
      await button.click({ timeout: actionTimeoutMs }).catch(() => null);
      return text;
    }
  }

  return null;
}

async function attemptSubmit(page, actionTimeoutMs) {
  const byRole = await clickByRolePatterns(page, SUBMIT_PATTERNS, actionTimeoutMs);
  if (byRole) {
    return byRole;
  }

  const submitLocator = page.locator('button[type="submit"], input[type="submit"]');
  const submitCount = await submitLocator.count();
  for (let i = 0; i < submitCount; i += 1) {
    const button = submitLocator.nth(i);
    const visible = await button.isVisible().catch(() => false);
    if (!visible) {
      continue;
    }

    const text = await buttonText(button);
    await button.click({ timeout: actionTimeoutMs }).catch(() => null);
    return text || "submit";
  }

  return null;
}

async function clickByRolePatterns(page, patterns, actionTimeoutMs) {
  for (const pattern of patterns) {
    const button = page.getByRole("button", { name: pattern }).first();
    const count = await button.count();

    if (count === 0) {
      continue;
    }

    const visible = await button.isVisible().catch(() => false);
    if (!visible) {
      continue;
    }

    await button.click({ timeout: actionTimeoutMs }).catch(() => null);
    return pattern.toString();
  }

  return null;
}

async function buttonText(button) {
  const text = await button.innerText().catch(() => "");
  if (sanitize(text)) {
    return sanitize(text).toLowerCase();
  }

  const value = await button.getAttribute("value").catch(() => "");
  return sanitize(value).toLowerCase();
}

function resolveMissingLabel(meta, key) {
  if (key) {
    const rule = FIELD_RULES.find((item) => item.key === key);
    if (rule) {
      return rule.label;
    }
  }

  return sanitize(meta.labelText || meta.placeholder || meta.name || meta.id || "Campo obrigatorio");
}

async function buildMetadata(page, extra = {}) {
  const title = await page.title().catch(() => null);
  const finalUrl = page.url();

  return {
    driver: "playwright",
    title,
    final_url: finalUrl,
    timestamp: new Date().toISOString(),
    ...extra
  };
}

function failureResult(message, logs, metadata = {}) {
  return {
    status: "failed",
    message,
    missing_fields: [],
    metadata: {
      driver: "playwright",
      ...metadata
    },
    logs
  };
}

function logInfo(logs, message, context) {
  logs.push({ level: "info", message, context: context || null });
}

function logWarn(logs, message, context) {
  logs.push({ level: "warning", message, context: context || null });
}

function logError(logs, message, context) {
  logs.push({ level: "error", message, context: context || null });
}

function uniqueList(list) {
  return Array.from(new Set(list.filter((item) => sanitize(item) !== "")));
}

function sanitize(value) {
  return String(value || "").trim();
}

function normalizeDate(value) {
  const cleaned = sanitize(value);
  if (/^\d{4}-\d{2}-\d{2}$/.test(cleaned)) {
    return cleaned;
  }

  const asDate = new Date(cleaned);
  if (Number.isNaN(asDate.getTime())) {
    return cleaned;
  }

  const year = String(asDate.getUTCFullYear());
  const month = String(asDate.getUTCMonth() + 1).padStart(2, "0");
  const day = String(asDate.getUTCDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function toPositiveInt(value, fallback) {
  const parsed = Number.parseInt(value, 10);
  if (Number.isNaN(parsed) || parsed <= 0) {
    return fallback;
  }
  return parsed;
}

async function readPayload() {
  const raw = await readStdin();
  if (!raw || sanitize(raw) === "") {
    return {};
  }

  try {
    return JSON.parse(raw);
  } catch (_error) {
    return {};
  }
}

function readStdin() {
  return new Promise((resolve, reject) => {
    let data = "";
    process.stdin.setEncoding("utf8");
    process.stdin.on("data", (chunk) => {
      data += chunk;
    });
    process.stdin.on("end", () => resolve(data));
    process.stdin.on("error", reject);
  });
}

function safeErrorMessage(error) {
  if (!error) {
    return "unknown_error";
  }

  if (typeof error === "string") {
    return error;
  }

  if (error instanceof Error) {
    return error.message;
  }

  return String(error);
}

main()
  .then((result) => {
    process.stdout.write(JSON.stringify(result));
  })
  .catch((error) => {
    const fallback = failureResult(
      "Falha inesperada no worker de automacao.",
      [{ level: "error", message: "Erro fatal no worker.", context: { error: safeErrorMessage(error) } }],
      { reason: "fatal_error" }
    );
    process.stdout.write(JSON.stringify(fallback));
  });

<script>
/**
 * Autocomplete for the sandbox editors. Monaco ships no IntelliSense for PHP, and the files mix
 * PHP, HTML, CSS and JS, so one provider picks the suggestion set from the cursor position.
 * Note: never write a literal PHP open tag here (Blade would try to compile it); use PHP_OPEN.
 */
(function () {
    const PHP_OPEN = '\x3C?php';
    const PHP_ECHO = '\x3C?=';
    const PHP_CLOSE = '?>';

    const SNIPPET = 4; // monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet

    // [label, snippet, detail]
    const PHP_SNIPPETS = [
        ['if', 'if (${1:condition}) {\n\t$0\n}', 'if'],
        ['ifelse', 'if (${1:condition}) {\n\t$2\n} else {\n\t$0\n}', 'if / else'],
        ['elseif', 'elseif (${1:condition}) {\n\t$0\n}', 'elseif'],
        ['foreach', 'foreach (${1:\\$array} as ${2:\\$value}) {\n\t$0\n}', 'foreach (value)'],
        ['foreachk', 'foreach (${1:\\$array} as ${2:\\$key} => ${3:\\$value}) {\n\t$0\n}', 'foreach (key => value)'],
        ['for', 'for (\\$${1:i} = 0; \\$${1:i} < ${2:10}; \\$${1:i}++) {\n\t$0\n}', 'for loop'],
        ['while', 'while (${1:condition}) {\n\t$0\n}', 'while loop'],
        ['switch', 'switch (${1:\\$var}) {\n\tcase ${2:value}:\n\t\t$0\n\t\tbreak;\n\tdefault:\n\t\tbreak;\n}', 'switch'],
        ['function', 'function ${1:name}(${2:\\$param}) {\n\t$0\n}', 'function'],
        ['class', 'class ${1:Name} {\n\tpublic function __construct(${2}) {\n\t\t$0\n\t}\n}', 'class'],
        ['try', 'try {\n\t$1\n} catch (Exception \\$e) {\n\techo \\$e->getMessage();\n}', 'try / catch'],
        ['echo', 'echo ${1:"text"};', 'echo'],
        ['isPost', 'if (\\$_SERVER[\'REQUEST_METHOD\'] === \'POST\') {\n\t$0\n}', 'handle a POST form'],
        ['pdoselect', '\\$stmt = \\$db->prepare(\'SELECT * FROM ${1:table} WHERE ${2:id} = ?\');\n\\$stmt->execute([${3:\\$id}]);\n\\$rows = \\$stmt->fetchAll(PDO::FETCH_ASSOC);', 'PDO prepared SELECT'],
        ['mysqliselect', '\\$result = mysqli_query(${1:\\$conn}, "SELECT * FROM ${2:table}");\nwhile (\\$row = mysqli_fetch_assoc(\\$result)) {\n\t$0\n}', 'mysqli SELECT loop'],
        ['phptag', PHP_OPEN + '\n$0\n' + PHP_CLOSE, 'PHP block'],
        ['phpecho', PHP_ECHO + ' ${1:\\$var} ' + PHP_CLOSE, 'PHP echo tag'],
    ];

    const PHP_KEYWORDS = ('echo print if elseif else foreach for while do switch case break continue return function class new try catch finally throw '
        + 'isset empty unset array list global static const public private protected extends implements interface namespace use null true false '
        + 'include require include_once require_once match fn abstract final readonly instanceof default').split(' ');

    const PHP_VARIABLES = [
        ['$_GET', 'Query-string data'], ['$_POST', 'Form data (POST)'], ['$_REQUEST', 'GET + POST + COOKIE'],
        ['$_SESSION', 'Session data'], ['$_COOKIE', 'Cookies'], ['$_SERVER', 'Server / request info'], ['$_FILES', 'Uploaded files'],
        ['$db', 'PDO connection to your database'], ['$sharedConns', 'mysqli connections to shared databases'],
        ['$sharedDbs', 'PDO connections to shared databases'],
    ];

    // [name, snippet args, detail]
    const PHP_FUNCTIONS = [
        ['strlen', '${1:\\$str}', 'int strlen(string)'], ['strtoupper', '${1:\\$str}', 'string'], ['strtolower', '${1:\\$str}', 'string'],
        ['ucfirst', '${1:\\$str}', 'string'], ['ucwords', '${1:\\$str}', 'string'], ['trim', '${1:\\$str}', 'string'],
        ['ltrim', '${1:\\$str}', 'string'], ['rtrim', '${1:\\$str}', 'string'], ['str_replace', '${1:\\$search}, ${2:\\$replace}, ${3:\\$subject}', 'string|array'],
        ['str_contains', '${1:\\$haystack}, ${2:\\$needle}', 'bool'], ['str_starts_with', '${1:\\$haystack}, ${2:\\$needle}', 'bool'],
        ['str_ends_with', '${1:\\$haystack}, ${2:\\$needle}', 'bool'], ['substr', '${1:\\$str}, ${2:\\$start}, ${3:\\$length}', 'string'],
        ['strpos', '${1:\\$haystack}, ${2:\\$needle}', 'int|false'], ['str_repeat', '${1:\\$str}, ${2:\\$times}', 'string'],
        ['str_pad', '${1:\\$str}, ${2:\\$length}, ${3:" "}', 'string'], ['strrev', '${1:\\$str}', 'string'], ['strcmp', '${1:\\$a}, ${2:\\$b}', 'int'],
        ['explode', '${1:","}, ${2:\\$str}', 'array'], ['implode', '${1:", "}, ${2:\\$array}', 'string'], ['sprintf', '${1:"%s"}, ${2:\\$value}', 'string'],
        ['printf', '${1:"%s"}, ${2:\\$value}', 'prints formatted string'], ['number_format', '${1:\\$num}, ${2:0}, ${3:","}, ${4:"."}', 'string'],
        ['htmlspecialchars', '${1:\\$str}', 'string (escape HTML)'], ['nl2br', '${1:\\$str}', 'string'], ['md5', '${1:\\$str}', 'string'],
        ['password_hash', '${1:\\$password}, PASSWORD_DEFAULT', 'string'], ['password_verify', '${1:\\$password}, ${2:\\$hash}', 'bool'],
        ['json_encode', '${1:\\$value}', 'string'], ['json_decode', '${1:\\$json}, true', 'mixed'],
        ['count', '${1:\\$array}', 'int'], ['array_push', '${1:\\$array}, ${2:\\$value}', 'int'], ['array_pop', '${1:\\$array}', 'mixed'],
        ['array_shift', '${1:\\$array}', 'mixed'], ['array_unshift', '${1:\\$array}, ${2:\\$value}', 'int'], ['array_merge', '${1:\\$a}, ${2:\\$b}', 'array'],
        ['array_keys', '${1:\\$array}', 'array'], ['array_values', '${1:\\$array}', 'array'], ['array_map', '${1:fn(\\$x) => \\$x}, ${2:\\$array}', 'array'],
        ['array_filter', '${1:\\$array}, ${2:fn(\\$x) => true}', 'array'], ['array_reduce', '${1:\\$array}, ${2:fn(\\$carry, \\$x) => \\$carry + \\$x}, ${3:0}', 'mixed'],
        ['array_sum', '${1:\\$array}', 'int|float'], ['array_search', '${1:\\$needle}, ${2:\\$array}', 'int|string|false'], ['in_array', '${1:\\$needle}, ${2:\\$array}', 'bool'],
        ['array_key_exists', '${1:\\$key}, ${2:\\$array}', 'bool'], ['array_slice', '${1:\\$array}, ${2:\\$offset}, ${3:\\$length}', 'array'],
        ['array_unique', '${1:\\$array}', 'array'], ['array_reverse', '${1:\\$array}', 'array'], ['array_column', '${1:\\$array}, ${2:"column"}', 'array'],
        ['array_combine', '${1:\\$keys}, ${2:\\$values}', 'array'], ['range', '${1:1}, ${2:10}', 'array'], ['sort', '${1:\\$array}', 'sort ascending'],
        ['rsort', '${1:\\$array}', 'sort descending'], ['usort', '${1:\\$array}, ${2:fn(\\$a, \\$b) => \\$a <=> \\$b}', 'custom sort'], ['asort', '${1:\\$array}', 'sort by value'],
        ['arsort', '${1:\\$array}', 'sort by value desc'], ['ksort', '${1:\\$array}', 'sort by key'], ['krsort', '${1:\\$array}', 'sort by key desc'],
        ['min', '${1:\\$a}, ${2:\\$b}', 'mixed'], ['max', '${1:\\$a}, ${2:\\$b}', 'mixed'], ['abs', '${1:\\$n}', 'number'], ['round', '${1:\\$n}, ${2:0}', 'float'],
        ['floor', '${1:\\$n}', 'float'], ['ceil', '${1:\\$n}', 'float'], ['sqrt', '${1:\\$n}', 'float'], ['pow', '${1:\\$base}, ${2:\\$exp}', 'number'],
        ['rand', '${1:1}, ${2:100}', 'int'], ['mt_rand', '${1:1}, ${2:100}', 'int'], ['intval', '${1:\\$v}', 'int'], ['floatval', '${1:\\$v}', 'float'],
        ['strval', '${1:\\$v}', 'string'], ['is_numeric', '${1:\\$v}', 'bool'], ['is_array', '${1:\\$v}', 'bool'], ['is_string', '${1:\\$v}', 'bool'], ['is_int', '${1:\\$v}', 'bool'],
        ['var_dump', '${1:\\$v}', 'dump a value'], ['print_r', '${1:\\$v}', 'print readable'], ['date', '${1:"d/m/Y H:i:s"}', 'string'], ['time', '', 'int (timestamp)'],
        ['strtotime', '${1:"now"}', 'int|false'], ['mktime', '${1:0}, ${2:0}, ${3:0}, ${4:1}, ${5:1}, ${6:2024}', 'int'], ['checkdate', '${1:\\$m}, ${2:\\$d}, ${3:\\$y}', 'bool'],
        ['session_start', '', 'start a session'], ['session_destroy', '', 'destroy the session'], ['setcookie', '${1:"name"}, ${2:"value"}, ${3:time() + 3600}', 'bool'],
        ['header', '${1:"Location: index.php"}', 'send a header'], ['isset', '${1:\\$var}', 'bool'], ['empty', '${1:\\$var}', 'bool'],
        ['mysqli_query', '${1:\\$conn}, ${2:"SELECT * FROM table"}', 'mysqli_result|bool'], ['mysqli_fetch_assoc', '${1:\\$result}', 'array|null'],
        ['mysqli_fetch_array', '${1:\\$result}', 'array|null'], ['mysqli_num_rows', '${1:\\$result}', 'int'], ['mysqli_real_escape_string', '${1:\\$conn}, ${2:\\$str}', 'string'],
        ['mysqli_error', '${1:\\$conn}', 'string'], ['mysqli_close', '${1:\\$conn}', 'bool'],
    ];

    const HTML_VOID = { meta: '<meta charset="UTF-8">', link: '<link rel="stylesheet" href="$1">', img: '<img src="$1" alt="$2">', input: '<input type="${1:text}" name="$2">', br: '<br>', hr: '<hr>' };
    const HTML_TAGS = ('html head title meta link style script body div span p h1 h2 h3 h4 h5 h6 a img ul ol li table thead tbody tr th td form input textarea '
        + 'select option label button br hr header footer nav section article main strong em pre code').split(' ');
    const HTML_ATTRS = ['class', 'id', 'style', 'href', 'src', 'alt', 'type', 'name', 'value', 'placeholder', 'required', 'action', 'method', 'for', 'target',
        'width', 'height', 'colspan', 'rowspan', 'checked', 'selected', 'disabled', 'readonly', 'min', 'max', 'maxlength', 'rel', 'charset', 'lang', 'onclick', 'onsubmit'];
    const HTML_SNIPPETS = [
        ['html5', '<!DOCTYPE html>\n<html lang="vi">\n<head>\n\t<meta charset="UTF-8">\n\t<title>${1:Tiêu đề}</title>\n</head>\n<body>\n\t$0\n</body>\n</html>', 'HTML5 page'],
        ['form', '<form method="${1:POST}" action="${2:}">\n\t$0\n\t<input type="submit" value="Gửi">\n</form>', 'form'],
        ['table', '<table border="1">\n\t<tr>\n\t\t<th>${1:Cột 1}</th>\n\t\t<th>${2:Cột 2}</th>\n\t</tr>\n\t<tr>\n\t\t<td>$3</td>\n\t\t<td>$4</td>\n\t</tr>\n</table>', 'table'],
        ['select', '<select name="${1:name}">\n\t<option value="${2:1}">${3:Một}</option>\n</select>', 'select'],
        ['ul', '<ul>\n\t<li>$0</li>\n</ul>', 'unordered list'],
        ['a', '<a href="${1:#}">${2:Liên kết}</a>', 'link'],
        ['button', '<button type="${1:submit}">${2:Gửi}</button>', 'button'],
    ];

    const CSS_PROPS = ('color background background-color border border-radius margin margin-top margin-bottom margin-left margin-right padding padding-top '
        + 'padding-bottom padding-left padding-right width height max-width min-width max-height min-height display position top left right bottom float clear '
        + 'font-size font-family font-weight font-style text-align text-decoration text-transform line-height letter-spacing flex flex-direction justify-content '
        + 'align-items gap grid grid-template-columns overflow opacity cursor box-shadow z-index list-style visibility transition transform').split(' ');

    const JS_SNIPPETS = [
        ['log', 'console.log(${1:value});', 'console.log'], ['getById', 'document.getElementById(\'${1:id}\')', 'getElementById'],
        ['qs', 'document.querySelector(\'${1:.selector}\')', 'querySelector'], ['qsa', 'document.querySelectorAll(\'${1:.selector}\')', 'querySelectorAll'],
        ['listen', '${1:element}.addEventListener(\'${2:click}\', function (e) {\n\t$0\n});', 'addEventListener'],
        ['function', 'function ${1:name}(${2}) {\n\t$0\n}', 'function'], ['arrow', '(${1}) => {\n\t$0\n}', 'arrow function'],
        ['for', 'for (let ${1:i} = 0; ${1:i} < ${2:10}; ${1:i}++) {\n\t$0\n}', 'for loop'], ['if', 'if (${1:condition}) {\n\t$0\n}', 'if'],
        ['alert', 'alert(${1:"text"});', 'alert'], ['const', 'const ${1:name} = ${2:value};', 'const'], ['let', 'let ${1:name} = ${2:value};', 'let'],
        ['timeout', 'setTimeout(() => {\n\t$0\n}, ${1:1000});', 'setTimeout'],
    ];
    const JS_KEYWORDS = 'const let var function return if else for while switch case break continue true false null undefined new this document window console'.split(' ');

    function detectContext(model, position) {
        const text = model.getValueInRange({ startLineNumber: 1, startColumn: 1, endLineNumber: position.lineNumber, endColumn: position.column });
        const open = Math.max(text.lastIndexOf(PHP_OPEN), text.lastIndexOf(PHP_ECHO));
        if (open > text.lastIndexOf(PHP_CLOSE)) return 'php';

        const lower = text.toLowerCase();
        const lastTag = lower.lastIndexOf('<');
        if (lastTag > lower.lastIndexOf('>') && /^<[a-z]/.test(lower.slice(lastTag))) {
            const inTag = lower.slice(lastTag);
            if (/\s/.test(inTag) && !/^<[a-z0-9-]*$/.test(inTag)) return 'attr';
        }
        if (lower.lastIndexOf('<style') > lower.lastIndexOf('</style')) return 'css';
        if (lower.lastIndexOf('<script') > lower.lastIndexOf('</script')) return 'js';
        return 'html';
    }

    function item(monaco, label, kind, insertText, detail, extra) {
        return Object.assign({ label, kind, insertText, detail, insertTextRules: SNIPPET }, extra || {});
    }

    function build(monaco, ctx, model, word, range) {
        const K = monaco.languages.CompletionItemKind;
        const out = [];

        if (ctx === 'php') {
            if (word.startsWith('$')) {
                const seen = new Set(PHP_VARIABLES.map(v => v[0]));
                PHP_VARIABLES.forEach(([name, doc]) => out.push(item(monaco, name, K.Variable, '\\' + name, doc, { range, filterText: name })));
                (model.getValue().match(/\$[A-Za-z_]\w*/g) || []).forEach(name => {
                    if (seen.has(name) || name === word) return;
                    seen.add(name);
                    out.push(item(monaco, name, K.Variable, '\\' + name, 'variable in this file', { range, filterText: name }));
                });
                return out;
            }
            PHP_SNIPPETS.forEach(([label, text, detail]) => out.push(item(monaco, label, K.Snippet, text, detail, { range, sortText: '0' + label })));
            PHP_FUNCTIONS.forEach(([name, args, detail]) => out.push(item(monaco, name, K.Function, name + '(' + args + ')', detail, { range, sortText: '1' + name })));
            PHP_KEYWORDS.forEach(k => out.push(item(monaco, k, K.Keyword, k, 'keyword', { range, sortText: '2' + k })));
            return out;
        }

        if (ctx === 'css') {
            CSS_PROPS.forEach(p => out.push(item(monaco, p, K.Property, p + ': ${1};', 'CSS property', { range })));
            return out;
        }

        if (ctx === 'js') {
            JS_SNIPPETS.forEach(([label, text, detail]) => out.push(item(monaco, label, K.Snippet, text, detail, { range, sortText: '0' + label })));
            JS_KEYWORDS.forEach(k => out.push(item(monaco, k, K.Keyword, k, 'JavaScript', { range, sortText: '1' + k })));
            return out;
        }

        if (ctx === 'attr') {
            HTML_ATTRS.forEach(a => out.push(item(monaco, a, K.Property, ['required', 'checked', 'selected', 'disabled', 'readonly'].includes(a) ? a : a + '="$1"', 'HTML attribute', { range })));
            return out;
        }

        // html
        if (word.startsWith('</')) {
            HTML_TAGS.filter(t => !HTML_VOID[t]).forEach(t => out.push(item(monaco, '</' + t + '>', K.Property, '</' + t + '>', 'closing tag', { range, filterText: '</' + t })));
            return out;
        }
        HTML_SNIPPETS.forEach(([label, text, detail]) => out.push(item(monaco, label, K.Snippet, text, detail, { range, sortText: '0' + label })));
        HTML_TAGS.forEach(t => {
            const text = HTML_VOID[t] || ('<' + t + '>$0</' + t + '>');
            out.push(item(monaco, '<' + t + '>', K.Property, text, 'HTML tag', { range, filterText: '<' + t, sortText: '1' + t }));
        });
        out.push(item(monaco, PHP_OPEN + ' ... ' + PHP_CLOSE, K.Snippet, PHP_OPEN + '\n$0\n' + PHP_CLOSE, 'PHP block', { range, filterText: '\x3C?', sortText: '0php' }));
        return out;
    }

    let registered = false;
    window.registerSandboxCompletions = function (monaco) {
        if (registered) return;
        registered = true;

        monaco.languages.registerCompletionItemProvider('php', {
            triggerCharacters: ['<', '$', ' ', '/'],
            provideCompletionItems(model, position, context) {
                const linePrefix = model.getLineContent(position.lineNumber).slice(0, position.column - 1);
                const ctx = detectContext(model, position);
                const m = linePrefix.match(/(<\/?[\w-]*|\$\w*|[\w-]+)$/);
                const word = m ? m[0] : '';
                if (context && context.triggerCharacter === ' ' && ctx !== 'attr') return { suggestions: [] };
                if (ctx === 'php' && word.startsWith('<')) return { suggestions: [] };
                if (ctx === 'html' && word && !word.startsWith('<') && !/^[a-z]/i.test(word)) return { suggestions: [] };

                const range = {
                    startLineNumber: position.lineNumber, endLineNumber: position.lineNumber,
                    startColumn: position.column - word.length, endColumn: position.column,
                };
                return { suggestions: build(monaco, ctx, model, word, range) };
            },
        });
    };

    window.sandboxEditorOptions = function () {
        return {
            quickSuggestions: { other: true, comments: false, strings: true },
            suggestOnTriggerCharacters: true,
            snippetSuggestions: 'top',
            tabCompletion: 'on',
            acceptSuggestionOnEnter: 'on',
            wordBasedSuggestions: 'currentDocument',
            autoClosingBrackets: 'always',
            autoClosingQuotes: 'always',
            autoSurround: 'languageDefined',
            bracketPairColorization: { enabled: true },
            tabSize: 4,
            insertSpaces: true,
        };
    };
})();
</script>

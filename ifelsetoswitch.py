""" Python3
Convert PHP code back and forth between using if/else vs. switch cases
SPECIFICALLY for that really long piece of code in data.php that handles the types of requests

I didn't like that Kevin used if-else, since I find it much easier to read switch cases.
But if you, future web-coordinator, prefer the if-else format, I added a function to go backwards.

Note that this assumes a pretty specific format, since I was lazy and didn't want to handle the general case,
so you may have to make a few modifications to the PHP code before you use this.

Copy-paste the code from that segment of data.php into a python file named input.py, like this:
    -------
    input = \"""
    <PHP code here>
    \"""
    -------
(Except without the backslashes)

<3 Kyle
"""

import re
from input import input

SIGNAL_OF_FIRST_IF = "if ( $_POST['type'] == "
SIGNAL_OF_NEW_ELSE = "} else if ( $_POST['type'] == "
TAB_STRING = '  '

SIGNAL_OF_SWITCH_START = "switch ( $_POST['type'] ) {"
SIGNAL_OF_SWITCH_CASE = "  case "
SIGNAL_OF_CASE_END = "    break;"


def ifElseToSwitch(inputText):
    """
    Convert the PHP input from using if/else to using switch cases instead
    :param inputText: the PHP input using if/else, as a string
    :return: the PHP code using switch cases, as a string
    """
    def getIfConditionValue(text, isFirst=False):
        pattern = '"(.*)"'
        search = re.search(pattern, text, re.IGNORECASE)
        return search.group(1)

    def getNewStringForIfCondition(condition, linesOfCondition):
        newString = TAB_STRING + 'case "' + condition + '":\n'
        newString += ('\n' + TAB_STRING).join(linesOfCondition)
        newString += '\n' + (TAB_STRING * 2) + 'break;\n\n'
        return newString

    lines = inputText.split('\n')
    output = ""
    currentCondition = None
    currentConditionLines = []
    for line in lines:
        if line.startswith(SIGNAL_OF_FIRST_IF):
            print('first if:', line)
            currentCondition = getIfConditionValue(line, isFirst=True)
            currentConditionLines = []

        elif line.startswith(SIGNAL_OF_NEW_ELSE):
            print('new condition: ', line)
            output += getNewStringForIfCondition(currentCondition, currentConditionLines)
            currentCondition = getIfConditionValue(line)
            currentConditionLines = []

        elif line.startswith('}'):
            output += getNewStringForIfCondition(currentCondition, currentConditionLines)
            output += '}\n'
            break

        elif currentCondition is not None:
            currentConditionLines.append(line)
    return output



def switchToIfElse(inputText):
    """
    Convert the PHP input from using switch cases to using if/else instead
    :param inputText the PHP code using switch cases, as a string
    :return: the PHP input using if/else, as a string
    """
    def getCaseValue(text):
        pattern = '"(.*)"'
        search = re.search(pattern, text, re.IGNORECASE)
        return search.group(1)

    def getNewStringForSwitchCase(condition, linesOfCondition, isFirst=False):
        newString = SIGNAL_OF_FIRST_IF if isFirst else SIGNAL_OF_NEW_ELSE
        newString += '"' + condition + '" ) {\n'
        newString += '\n'.join([line.replace(TAB_STRING, '', 1) for line in linesOfCondition])
        newString += '\n'
        return newString

    lines = inputText.split('\n')
    output = ""
    isFirst = None
    currentCondition = None
    currentConditionLines = []
    for line in lines:
        if line.startswith(SIGNAL_OF_SWITCH_START):
            continue

        elif line.startswith(SIGNAL_OF_SWITCH_CASE):
            print('new condition: ', line)
            currentCondition = getCaseValue(line)
            currentConditionLines = []

        elif line.startswith(SIGNAL_OF_CASE_END):
            if isFirst is None:
                isFirst = True
            elif isFirst:
                isFirst = False
                output += getNewStringForSwitchCase(currentCondition, currentConditionLines, isFirst=True)
            else:
                output += getNewStringForSwitchCase(currentCondition, currentConditionLines)

        elif line.startswith('}'):
            output += '}'
            break

        elif currentCondition is not None:
            currentConditionLines.append(line)
    return output


# print(output)
# with open('output.php', 'w') as f:
#     f.write(output)
